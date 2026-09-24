<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcastPush;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use App\Models\Broadcast;
use App\Services\FirestoreUserDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Bulk push messaging — push-only (no email/SMS channel), matching the
 * client's confirmed scope. Sending runs inline (see store()'s doc
 * comment) rather than through a real queue: this project has no deployed
 * queue-worker process, so a queued dispatch here would just sit in the
 * `jobs` table forever — SendBroadcastPush itself is unchanged and still
 * dispatch()-able the normal way once real hosting + a worker exist.
 */
class BroadcastController extends Controller
{
    /**
     * Session key for the one-time confirm token minted by preview() and
     * consumed by store() — see store()'s doc comment for why this exists.
     */
    private const CONFIRM_TOKEN_KEY = 'broadcast_confirm_token';

    public function __construct(private readonly FirestoreUserDirectory $directory)
    {
    }

    public function index(): View
    {
        $broadcasts = Broadcast::with('admin')->latest()->paginate(20);

        return view('admin.broadcasts.index', ['broadcasts' => $broadcasts]);
    }

    public function create(): View
    {
        return view('admin.broadcasts.create');
    }

    /**
     * Recipient-count preview for the confirmation step — an XHR-free page
     * reload with the same form values, not a separate JS-driven endpoint.
     * Also mints the one-time confirm token store() requires, so the
     * confirm page itself can't be resubmitted into a second broadcast.
     */
    public function preview(Request $request): View
    {
        $data = $this->validated($request);
        $filters = $this->audienceFilters($data);
        $token = Str::random(40);
        session([self::CONFIRM_TOKEN_KEY => $token]);

        return view('admin.broadcasts.confirm', [
            'data' => $data,
            'filters' => $filters,
            'recipientEstimate' => $this->estimateRecipients($filters),
            'confirmToken' => $token,
            'recipientNames' => $this->recipientNames($data),
        ]);
    }

    /**
     * Guarded by a synchronizer token (session-stored, single-use) against
     * double submission — a double-click on "Confirm & send", or
     * resubmitting this same POST (back button, browser replay) — which
     * would otherwise create two identical Broadcast rows and send every
     * recipient the message twice. The token is forgotten the instant it's
     * checked, before the Broadcast row is even created, so a
     * resubmission always fails this check rather than racing the first
     * request to create its own row.
     *
     * Sends inline via SendBroadcastPush::dispatchSync() rather than a
     * real queued dispatch() — see class doc comment. set_time_limit
     * guards this request against PHP's default execution cap on a large
     * future audience; the job's own per-chunk incremental sent_count/
     * failed_count saves mean even a rare timeout still leaves accurate
     * partial progress on the Broadcast row rather than looking stuck.
     */
    public function store(Request $request): RedirectResponse
    {
        $submittedToken = (string) $request->input('confirm_token');
        $expectedToken = session(self::CONFIRM_TOKEN_KEY);
        session()->forget(self::CONFIRM_TOKEN_KEY);

        if (!$expectedToken || !hash_equals($expectedToken, $submittedToken)) {
            return redirect()->route('admin.broadcasts.create')
                ->withErrors(['confirm_token' => 'This broadcast may already have been sent — please compose it again.'])
                ->withInput($request->except(['confirm_token']));
        }

        $data = $this->validated($request);
        $filters = $this->audienceFilters($data);

        $broadcast = Broadcast::create([
            'admin_id' => $this->admin()->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience_filters' => $filters,
            'recipient_estimate' => $this->estimateRecipients($filters),
            'status' => 'pending',
        ]);

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        SendBroadcastPush::dispatchSync($broadcast->id);
        $broadcast->refresh();

        AdminAuditLog::recordFor($this->admin(), 'broadcast.sent', 'broadcast', (string) $broadcast->id, [
            'audience_filters' => $filters,
            'recipient_estimate' => $broadcast->recipient_estimate,
            'sent_count' => $broadcast->sent_count,
            'failed_count' => $broadcast->failed_count,
        ]);

        if ($broadcast->status === 'failed') {
            return redirect()->route('admin.broadcasts.index')
                ->with('error', "Broadcast failed to send: {$broadcast->error}");
        }

        return redirect()->route('admin.broadcasts.index')->with(
            'status',
            "Broadcast sent — {$broadcast->sent_count} delivered".
                ($broadcast->failed_count > 0 ? ", {$broadcast->failed_count} failed" : '').'.',
        );
    }

    /**
     * JSON typeahead backing the "Specific users…" audience picker
     * (create.blade.php) — same FirestoreUserDirectory::search() the full
     * admin Users page already uses, just returned as JSON instead of a
     * rendered page.
     */
    public function searchRecipients(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['users' => []]);
        }

        $result = $this->directory->search(['search' => $q], 10);
        $users = array_map(
            static fn (array $u) => [
                'uid' => $u['id'],
                'name' => $u['name'] ?? $u['id'],
                'email' => $u['email'] ?? null,
            ],
            $result['users'],
        );

        return response()->json(['users' => $users]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'audience_type' => ['required', Rule::in(['everyone', 'role', 'is_vip', 'native_lang', 'learning_lang', 'specific_users'])],
            'audience_value' => ['required_unless:audience_type,everyone,specific_users', 'nullable', 'string', 'max:255'],
            'recipients_json' => ['required_if:audience_type,specific_users', 'nullable', 'string'],
        ]);
    }

    private function audienceFilters(array $data): array
    {
        if ($data['audience_type'] === 'specific_users') {
            return ['uids' => $this->parseRecipientUids($data['recipients_json'] ?? '')];
        }
        if ($data['audience_type'] === 'everyone') {
            return [];
        }

        return [$data['audience_type'] => $data['audience_value']];
    }

    /**
     * Decodes the create form's hidden recipients_json field
     * ([{uid,name}, ...]) into a deduplicated, non-empty uid list —
     * defensive against malformed/empty client input regardless of what
     * the picker's own JS guarantees, so a broadcast can never silently
     * end up targeting nobody (or, worse, falling through to
     * audienceFilters()'s "everyone" shape).
     */
    private function parseRecipientUids(string $json): array
    {
        $decoded = json_decode($json, true);
        $uids = is_array($decoded)
            ? array_values(array_unique(array_filter(
                array_map(static fn ($row) => is_array($row) ? (string) ($row['uid'] ?? '') : '', $decoded),
            )))
            : [];

        if ($uids === []) {
            throw ValidationException::withMessages([
                'recipients_json' => 'Select at least one recipient.',
            ]);
        }

        return $uids;
    }

    private function recipientNames(array $data): ?array
    {
        if ($data['audience_type'] !== 'specific_users') {
            return null;
        }
        $decoded = json_decode($data['recipients_json'] ?? '', true);

        return is_array($decoded)
            ? array_values(array_filter(array_map(static fn ($row) => is_array($row) ? ($row['name'] ?? null) : null, $decoded)))
            : [];
    }

    /**
     * Every broadcast now posts a FaceTalk chat message to every matched
     * Firestore user (push is a subset — only those with a saved token) —
     * see SendBroadcastPush — so the estimate is the Firestore match count
     * for both "everyone" and a filtered audience alike. For hand-picked
     * recipients the count is already exact — no Firestore query needed.
     */
    private function estimateRecipients(array $filters): int
    {
        if (isset($filters['uids'])) {
            return count($filters['uids']);
        }

        return $this->directory->count($filters);
    }

    private function admin(): Admin
    {
        return Auth::guard('admin')->user();
    }
}
