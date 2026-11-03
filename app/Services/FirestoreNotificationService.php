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

    private function accessToken(): string
    {
        return Cache::remember('firestore_access_token', now()->addMinutes(50), function () {
            if (!$this->credentialsPath || !file_exists(base_path($this->credentialsPath))) {
                throw new RuntimeException(
                    'Firebase credentials not found — see FIREBASE_CREDENTIALS in .env ' .
                    '(same file NotificationController uses for FCM).'
                );
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/datastore',
                base_path($this->credentialsPath),
            );
            $token = $credentials->fetchAuthToken();

            return $token['access_token'];
        });
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
