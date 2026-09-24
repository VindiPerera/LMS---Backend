<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Services\FirestoreChatBroadcastService;
use App\Services\FirestoreReportService;
use App\Services\FirestoreUserDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;

/**
 * Manages the app's real users — Firebase Auth + Firestore's `users/{uid}`
 * (see FirestoreUserDirectory's doc). The Laravel `users` table is a
 * separate, currently-unused identity system and is intentionally not
 * what this screen manages.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly FirestoreUserDirectory $directory,
        private readonly FirebaseAuth $firebaseAuth,
        private readonly FirestoreReportService $reports,
        private readonly FirestoreChatBroadcastService $chatBroadcast,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'role' => (string) $request->query('role', ''),
            'is_vip' => (string) $request->query('is_vip', ''),
            'native_lang' => (string) $request->query('native_lang', ''),
            'learning_lang' => (string) $request->query('learning_lang', ''),
        ];

        $result = $this->directory->search($filters, 20, $request->query('after'));

        return view('admin.users.index', [
            'users' => $result['users'],
            'nextCursor' => $result['next_cursor'],
            'filters' => $filters,
        ]);
    }

    public function edit(string $uid): View
    {
        $profile = $this->directory->find($uid);
        abort_if($profile === null, 404, 'No Firestore profile for this user.');

        try {
            $authRecord = $this->firebaseAuth->getUser($uid);
        } catch (UserNotFound) {
            $authRecord = null;
        }

        return view('admin.users.edit', [
            'uid' => $uid,
            'profile' => $profile,
            'authRecord' => $authRecord,
            'reports' => $this->reports->forUser($uid),
            'reportCount' => $this->reports->countForUser($uid),
            'moderationHistory' => AdminAuditLog::where('target_type', 'firebase_user')
                ->where('target_id', $uid)
                ->latest()
                ->get(),
        ]);
    }

    public function update(Request $request, string $uid): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:student,teacher'],
            'is_vip' => ['sometimes', 'boolean'],
            'native_lang' => ['nullable', 'string', 'max:255'],
            'learning_lang' => ['nullable', 'string', 'max:255'],
        ]);

        $fields = [
            'name' => $data['name'],
            'role' => $data['role'],
            'isVip' => $request->boolean('is_vip'),
            'nativeLang' => $data['native_lang'] ?? '',
            'learningLang' => $data['learning_lang'] ?? '',
        ];

        $this->directory->updateFields($uid, $fields);

        AdminAuditLog::recordFor($this->admin(), 'user.updated', 'firebase_user', $uid, ['fields' => array_keys($fields)]);

        return back()->with('status', 'Profile updated.');
    }

    /**
     * Disables the Firebase Auth account and revokes its refresh tokens —
     * Firebase itself then rejects every future token refresh, so this
     * takes effect immediately without any change on the Flutter side.
     */
    public function ban(Request $request, string $uid): RedirectResponse
    {
        $data = $request->validate(['ban_reason' => ['nullable', 'string', 'max:255']]);

        $this->firebaseAuth->disableUser($uid);
        $this->firebaseAuth->revokeRefreshTokens($uid);
        $this->directory->updateFields($uid, ['banReason' => $data['ban_reason'] ?? '']);

        AdminAuditLog::recordFor($this->admin(), 'user.banned', 'firebase_user', $uid, ['ban_reason' => $data['ban_reason'] ?? null]);

        return back()->with('status', 'User banned.');
    }

    public function unban(string $uid): RedirectResponse
    {
        $this->firebaseAuth->enableUser($uid);
        $this->directory->updateFields($uid, ['banReason' => '']);

        AdminAuditLog::recordFor($this->admin(), 'user.unbanned', 'firebase_user', $uid);

        return back()->with('status', 'User unbanned.');
    }

    /**
     * Revokes refresh tokens without disabling the account — "sign out
     * everywhere" for a support request, not a ban.
     */
    public function forceLogout(string $uid): RedirectResponse
    {
        $this->firebaseAuth->revokeRefreshTokens($uid);

        AdminAuditLog::recordFor($this->admin(), 'user.force_logout', 'firebase_user', $uid);

        return back()->with('status', 'User signed out on all devices.');
    }

    /**
     * Firebase sends this itself (its own hosted email template) — no
     * Laravel mailer involved, unlike the (dormant) Laravel-side reset flow.
     */
    public function sendPasswordReset(string $uid): RedirectResponse
    {
        try {
            $email = $this->firebaseAuth->getUser($uid)->email;
        } catch (UserNotFound) {
            return back()->with('status', 'No Firebase Auth account for this user (Google/other provider only?).');
        }

        if (!$email) {
            return back()->with('status', 'This account has no email address on file.');
        }

        $this->firebaseAuth->sendPasswordResetLink($email);

        AdminAuditLog::recordFor($this->admin(), 'user.password_reset_sent', 'firebase_user', $uid);

        return back()->with('status', "Password reset email sent to {$email}.");
    }

    /**
     * Posts a moderation warning into the user's chat list, the same
     * read-only "FaceTalk Company" mechanism broadcasts already use (see
     * FirestoreChatBroadcastService's doc) — a single-recipient send.
     */
    public function warn(Request $request, string $uid): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);

        $this->chatBroadcast->sendToMany([$uid], $data['message']);

        AdminAuditLog::recordFor($this->admin(), 'user.warned', 'firebase_user', $uid, ['message' => $data['message']]);

        return back()->with('status', 'Warning sent.');
    }

    /**
     * Permanently deletes the Firebase Auth account and the Firestore
     * `users/{uid}` profile document. Scope note: this does NOT cascade to
     * every moment/message/room-history document the uid ever touched —
     * those remain, orphaned by uid, exactly as e.g. a deleted Moments post
     * author already can happen today. A full content cascade is a
     * materially bigger, separate feature; this matches what "delete the
     * account" needs for moderation (the account can no longer be used,
     * signed into, or found in the user directory).
     */
    public function destroy(Request $request, string $uid): RedirectResponse
    {
        $profile = $this->directory->find($uid);
        abort_if($profile === null, 404, 'No Firestore profile for this user.');

        $data = $request->validate(['confirm_name' => ['required', 'string']]);
        if ($data['confirm_name'] !== ($profile['name'] ?? '')) {
            return back()->withErrors(['confirm_name' => 'Name does not match — account was not deleted.']);
        }

        try {
            $this->firebaseAuth->deleteUser($uid);
        } catch (UserNotFound) {
            // No Auth record (already deleted, or Firestore-only profile) — still remove the Firestore doc below.
        }
        $this->directory->delete($uid);

        AdminAuditLog::recordFor($this->admin(), 'user.deleted', 'firebase_user', $uid, ['name' => $profile['name'] ?? null]);

        return redirect()->route('admin.users.index')->with('status', 'Account permanently deleted.');
    }

    private function admin(): \App\Models\Admin
    {
        return Auth::guard('admin')->user();
    }
}
