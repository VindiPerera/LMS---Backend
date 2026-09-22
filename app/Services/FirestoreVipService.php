<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Grants VIP status on a successful payment by writing straight to the
 * same Firestore `users/{uid}` document the Flutter app reads
 * (AppUser.isVip / AppUser.vipExpiresAt — see vip_calendar_screen.dart).
 *
 * Uses the Firestore REST API directly with a token from the same service
 * account credentials already set up for FCM (FIREBASE_CREDENTIALS in
 * .env — see NotificationController) rather than pulling in the full
 * google/cloud-firestore client library, which drags in a heavy gRPC
 * dependency tree for what's just one document patch.
 */
class FirestoreVipService
{
    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-52f9b');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    private function getAccessToken(): string
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

    /**
     * Sets `isVip: true` and `vipExpiresAt` (now + [days]) on
     * `users/{uid}`. Extends from the user's *current* expiry if they're
     * already an active VIP (stacks remaining time), otherwise from now.
     */
    public function grantVip(string $uid, int $days): void
    {
        $token = $this->getAccessToken();
        $base = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/users/{$uid}";

        $currentExpiry = null;
        $existing = Http::withToken($token)->get($base);
        if ($existing->successful()) {
            $iso = $existing->json('fields.vipExpiresAt.timestampValue');
            if ($iso) {
                try {
                    $parsed = new \DateTimeImmutable($iso);
                    if ($parsed > new \DateTimeImmutable('now')) {
                        $currentExpiry = $parsed;
                    }
                } catch (\Exception) {
                    // Malformed/missing — fall through to "extend from now".
                }
            }
        }

        $newExpiry = ($currentExpiry ?? new \DateTimeImmutable('now'))->modify("+{$days} days");

        $response = Http::withToken($token)
            ->patch($base . '?updateMask.fieldPaths=isVip&updateMask.fieldPaths=vipExpiresAt', [
                'fields' => [
                    'isVip' => ['booleanValue' => true],
                    'vipExpiresAt' => ['timestampValue' => $newExpiry->format('Y-m-d\TH:i:s\Z')],
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Failed to grant VIP in Firestore: ' . $response->body());
        }
    }
}
