<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Writes the in-app notification feed entry (`notifications/{uid}/items`)
 * that notification_screen.dart / notification_badge.dart read — the
 * missing half of NotificationController::sendPush, which already sends
 * the FCM push for the same event but (until now) never created the
 * Firestore doc the bell icon actually reads.
 *
 * firestore.rules only lets a user write into their OWN notifications
 * feed (`allow read, write: if isOwner(uid)`), so the Flutter client can
 * never create one of these for someone else — the only way to notify
 * another user is from here, with the service-account credentials that
 * bypass that rule. Same REST-over-gRPC approach as FirestoreVipService /
 * FirestoreUserDirectory, for the same reason (no google/cloud-firestore
 * dependency for a handful of simple document writes).
 */
class FirestoreNotificationService
{
    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-52f9b');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    /**
     * Creates one `notifications/{uid}/items/{auto-id}` doc. Never throws —
     * called fire-and-forget right alongside the FCM push in
     * NotificationController::sendPush, and a failure here shouldn't fail
     * that request (the push itself may still have gone out fine).
     */
    public function createItem(string $uid, array $fields): void
    {
        if ($uid === '') {
            return;
        }

        try {
            $token = $this->accessToken();
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/notifications/{$uid}/items";

            $response = Http::withToken($token)->post($url, [
                'fields' => [
                    ...collect($fields)->map(fn ($v) => $this->encodeValue($v))->all(),
                    'isRead' => ['booleanValue' => false],
                    'createdAt' => ['timestampValue' => gmdate('Y-m-d\TH:i:s\Z')],
                ],
            ]);

            if (!$response->successful()) {
                Log::warning("FirestoreNotificationService::createItem({$uid}) failed: {$response->body()}");
            }
        } catch (\Throwable $e) {
            Log::warning("FirestoreNotificationService::createItem({$uid}) threw: {$e->getMessage()}");
        }
    }

    /**
     * Repairs `actorAvatar` (and `actorName`) on every already-existing
     * notification item [actorUid] is the actor of, across every
     * recipient's own `notifications/{uid}/items` subcollection.
     *
     * Unlike moments/voice rooms — which a user can fan their own profile
     * changes out to because they own those documents — a notification item
     * lives under its RECIPIENT's uid, and firestore.rules only lets the
     * owner (the recipient) write there (see that file's comment on this
     * collection). [actorUid] can never be the recipient of their own
     * "X liked your moment" notification, so the client has no write path
     * at all for these — only this service account (bypasses rules) does.
     *
     * Without this, a notification created while the actor's avatarUrl was
     * still unset/stale (e.g. before they ever uploaded a profile photo)
     * stays that way forever — there's nothing else that ever revisits it.
     * Called fire-and-forget, best-effort, from NotificationController, the
     * same self-heal spot as MomentService.updateAuthorInfoAcrossMoments on
     * the Flutter side.
     */
    public function backfillActorAvatar(string $actorUid, string $avatarUrl, string $actorName): void
    {
        if ($actorUid === '') {
            return;
        }

        try {
            $token = $this->accessToken();
            $queryUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";

            $response = Http::withToken($token)->post($queryUrl, [
                'structuredQuery' => [
                    'from' => [['collectionId' => 'items', 'allDescendants' => true]],
                    'where' => [
                        'fieldFilter' => [
                            'field' => ['fieldPath' => 'actorId'],
                            'op' => 'EQUAL',
                            'value' => ['stringValue' => $actorUid],
                        ],
                    ],
                    // Generous but bounded — this app's scale doesn't need
                    // real pagination here, just a ceiling against a runaway
                    // query if it ever did.
                    'limit' => 1000,
                ],
            ]);

            if (!$response->successful()) {
                Log::warning("FirestoreNotificationService::backfillActorAvatar({$actorUid}) query failed: {$response->body()}");
                return;
            }

            foreach ($response->json() ?? [] as $row) {
                $document = $row['document'] ?? null;
                if (!$document) {
                    continue; // heartbeat row, no document
                }

                $fields = $document['fields'] ?? [];
                $currentAvatar = $fields['actorAvatar']['stringValue'] ?? '';
                $currentName = $fields['actorName']['stringValue'] ?? '';
                if ($currentAvatar === $avatarUrl && $currentName === $actorName) {
                    continue; // already in sync — no write needed
                }

                // $document['name'] is the bare resource path (projects/.../
                // documents/notifications/{uid}/items/{id}) — runQuery
                // doesn't prefix it with the REST host the way a direct
                // get/post response does.
                $patchUrl = "https://firestore.googleapis.com/v1/{$document['name']}";
                Http::withToken($token)->patch(
                    "{$patchUrl}?updateMask.fieldPaths=actorAvatar&updateMask.fieldPaths=actorName",
                    [
                        'fields' => [
                            'actorAvatar' => ['stringValue' => $avatarUrl],
                            'actorName' => ['stringValue' => $actorName],
                        ],
                    ],
                );
            }
        } catch (\Throwable $e) {
            Log::warning("FirestoreNotificationService::backfillActorAvatar({$actorUid}) threw: {$e->getMessage()}");
        }
    }

    private function accessToken(): string
    {
        return Cache::remember('firestore_access_token', now()->addMinutes(50), function () {
            $path = $this->resolvedCredentialsPath();
            if (!$path || !file_exists($path)) {
                throw new RuntimeException(
                    'Firebase credentials not found — see FIREBASE_CREDENTIALS in .env ' .
                    '(same file NotificationController uses for FCM).'
                );
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/datastore',
                $path,
            );
            $token = $credentials->fetchAuthToken();

            return $token['access_token'];
        });
    }

    /**
     * FIREBASE_CREDENTIALS is meant to be an absolute path to a file kept
     * outside the web root (see .env's comment on it) — base_path() doesn't
     * know that and will happily concatenate the project root onto an
     * already-absolute path, so only relative paths get it applied.
     */
    private function resolvedCredentialsPath(): ?string
    {
        if (!$this->credentialsPath) {
            return null;
        }

        $isAbsolute = str_starts_with($this->credentialsPath, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $this->credentialsPath) === 1;

        return $isAbsolute ? $this->credentialsPath : base_path($this->credentialsPath);
    }

    private function encodeValue(mixed $value): array
    {
        return match (true) {
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_null($value) => ['nullValue' => null],
            default => ['stringValue' => (string) $value],
        };
    }
}
