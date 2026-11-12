<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
use App\Services\FirestoreNotificationService;
use App\Services\FirestoreUserDirectory;

/**
 * Server-side push notification sending, replacing the Cloud Functions ->
 * Firestore pipeline (functions/index.js in hello-frontend) that this
 * project doesn't deploy — Cloud Functions requires Firebase's paid Blaze
 * plan even at $0 real usage, which was a hard no. This backend already
 * runs for Moments media uploads, so it's the free server-side hop instead:
 *
 *   Flutter (sender) -> POST /api/notifications/push -> this controller
 *       -> Firebase Cloud Messaging -> Flutter (recipient's device)
 *
 * The service account credentials that make this possible live only here
 * (FIREBASE_CREDENTIALS in .env) — never in the Flutter app, which is
 * exactly why a client can't send pushes to other users directly in the
 * first place.
 */
class NotificationController extends Controller
{
    /** Notification types that belong in the in-app bell-icon feed
     * (`notifications/{uid}/items`) — everything else (currently just
     * `chat`) is push-only, on purpose: chat already has its own unread
     * badge in the chat list, so duplicating it into this feed too would
     * just be noise. Must stay in sync with NotificationType in
     * lib/models/notification_model.dart. */
    private const IN_APP_FEED_TYPES = ['like', 'comment', 'reshare', 'mention', 'voiceroom'];

    public function __construct(
        private readonly Messaging $messaging,
        private readonly FirestoreNotificationService $notifications,
        private readonly FirestoreUserDirectory $users,
    ) {
    }

    /**
     * Upserts the caller's current FCM registration token. Called from
     * push_notification_service.dart whenever a token is obtained or
     * refreshed — same moment it also (harmlessly) writes the token to
     * Firestore's users/{uid}.fcmToken.
     */
    public function saveToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uid' => ['required', 'string', 'max:191'],
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        FcmToken::updateOrCreate(
            ['uid' => $request->input('uid')],
            ['token' => $request->input('token')],
        );

        return response()->json(['success' => true]);
    }

    /**
     * Sends a push notification to one recipient uid. Generic across every
     * notification type this app has (chat message, friend request,
     * friend accept, voice room invite, moment like/comment/reshare/
     * mention) — the caller supplies title/body/data, this just resolves
     * the recipient's token and picks the right Android notification
     * channel from `data.type` (must match a channel actually created in
     * push_notification_service.dart's push_notification_service.dart, or
     * it silently falls back to Android's default channel).
     *
     * Always returns success:true even when there's no token to send to
     * (recipient has never opened the app, or is signed out everywhere) —
     * that's an expected, non-error outcome for the caller.
     */
    public function sendPush(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_uid' => ['required', 'string', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'data' => ['sometimes', 'array'],
            'data.*' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $recipientUid = $request->input('recipient_uid');
        $title = $request->input('title');
        $body = $request->input('body');
        $data = $request->input('data', []);

        // In-app bell-icon feed entry — written regardless of whether the
        // recipient has a push token registered below (that's a separate,
        // best-effort delivery channel; the in-app feed should still show
        // this even if the recipient has never granted push permission or
        // is on a device without one yet).
        $type = $data['type'] ?? null;
        if (in_array($type, self::IN_APP_FEED_TYPES, true) && !empty($data['actorId'])) {
            $this->notifications->createItem($recipientUid, [
                'type' => $type,
                'actorId' => $data['actorId'],
                'actorName' => $title,
                'actorAvatar' => $data['actorAvatar'] ?? '',
                'postId' => $data['postId'] ?? $data['roomId'] ?? '',
            ]);
        }

        $tokenRow = FcmToken::where('uid', $recipientUid)->first();
        if (!$tokenRow) {
            return response()->json(['success' => true, 'delivered' => false, 'reason' => 'no_token']);
        }

        try {
            $message = CloudMessage::fromArray([
                'token' => $tokenRow->token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => $this->androidChannelFor($type),
                        'notification_priority' => 'PRIORITY_MAX',
                        'visibility' => 'PUBLIC',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => ['sound' => 'default', 'content-available' => 1],
                    ],
                ],
                'data' => array_map('strval', $data),
            ]);

            $this->messaging->send($message);
            return response()->json(['success' => true, 'delivered' => true]);
        } catch (MessagingException|FirebaseException $e) {
            // A stale/uninstalled-app token is routine, not a real error —
            // log and still return success so the caller (e.g. sendMessage
            // in chat_service.dart) doesn't fail the action that triggered
            // this over an undeliverable notification.
            Log::warning("Push to {$recipientUid} failed: {$e->getMessage()}");
            return response()->json(['success' => true, 'delivered' => false, 'reason' => 'send_failed']);
        }
    }

    /**
     * Repairs actorAvatar/actorName on every already-existing notification
     * item [uid] is the actor of (across every recipient's own feed) to
     * match their CURRENT `users/{uid}` profile — see
     * FirestoreNotificationService::backfillActorAvatar's doc for why only
     * this service-account-backed endpoint can do that at all. Looks the
     * current name/avatar up itself from Firestore rather than trusting
     * whatever the caller supplies, so this can't be used to graffiti
     * someone else's past notifications with an arbitrary name/image.
     *
     * Called fire-and-forget from AuthService.init() on the Flutter side —
     * same self-heal spot as the moments/voice-room fan-out — so a no-op
     * run (the common case) costs one Firestore query and nothing else.
     */
    public function backfillActorAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'uid' => ['required', 'string', 'max:191'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $uid = $request->input('uid');
        $user = $this->users->find($uid);
        if ($user === null) {
            return response()->json(['success' => true, 'repaired' => false, 'reason' => 'user_not_found']);
        }

        $this->notifications->backfillActorAvatar(
            $uid,
            (string) ($user['avatarUrl'] ?? ''),
            (string) ($user['name'] ?? ''),
        );

        return response()->json(['success' => true, 'repaired' => true]);
    }

    /**
     * Maps a notification `type` to one of the Android channels created
     * client-side in push_notification_service.dart (all Importance.high)
     * — must stay in sync with that file's `_channelFor`.
     */
    private function androidChannelFor(?string $type): string
    {
        if ($type === 'chat') {
            return 'chat_channel';
        }
        if (in_array($type, ['friendRequest', 'friendAccept', 'voiceroom'], true)) {
            return 'social_channel';
        }
        return 'moments_channel'; // like / comment / reshare / mention
    }
}
