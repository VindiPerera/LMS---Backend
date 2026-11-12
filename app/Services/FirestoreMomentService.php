<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Admin-side counterpart to MomentService.updateAuthorInfoAcrossMoments on
 * the Flutter side. That client method only ever runs inside the AUTHOR's
 * own session (AuthService.init()/updateProfile()) — a user's past moments
 * stay stale until they personally reopen the app after changing their
 * photo, which another viewer restarting their own app can't trigger. This
 * gives the backend a way to repair them directly with the service account,
 * the same REST-over-gRPC approach as FirestoreNotificationService.
 */
class FirestoreMomentService
{
    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-52f9b');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    /**
     * Repairs `user.name`/`user.avatarUrl` on every `moments/{id}` doc
     * authored by [uid] to match the given current values. Mirrors
     * FirestoreNotificationService::backfillActorAvatar's diff-then-patch
     * shape — a no-op run costs one query and zero writes.
     */
    public function backfillAuthorInfo(string $uid, string $avatarUrl, string $name): int
    {
        if ($uid === '') {
            return 0;
        }

        $patched = 0;

        try {
            $token = $this->accessToken();
            $queryUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents:runQuery";

            $response = Http::withToken($token)->post($queryUrl, [
                'structuredQuery' => [
                    'from' => [['collectionId' => 'moments']],
                    'where' => [
                        'fieldFilter' => [
                            'field' => ['fieldPath' => 'user.id'],
                            'op' => 'EQUAL',
                            'value' => ['stringValue' => $uid],
                        ],
                    ],
                    'limit' => 1000,
                ],
            ]);

            if (!$response->successful()) {
                Log::warning("FirestoreMomentService::backfillAuthorInfo({$uid}) query failed: {$response->body()}");
                return 0;
            }

            foreach ($response->json() ?? [] as $row) {
                $document = $row['document'] ?? null;
                if (!$document) {
                    continue; // heartbeat row, no document
                }

                $userField = $document['fields']['user']['mapValue']['fields'] ?? [];
                $currentAvatar = $userField['avatarUrl']['stringValue'] ?? '';
                $currentName = $userField['name']['stringValue'] ?? '';
                if ($currentAvatar === $avatarUrl && $currentName === $name) {
                    continue; // already in sync
                }

                // $document['name'] is the bare resource path, same as
                // FirestoreNotificationService::backfillActorAvatar's note.
                $patchUrl = "https://firestore.googleapis.com/v1/{$document['name']}";
                Http::withToken($token)->patch(
                    "{$patchUrl}?updateMask.fieldPaths=user.avatarUrl&updateMask.fieldPaths=user.name",
                    [
                        'fields' => [
                            'user' => [
                                'mapValue' => [
                                    'fields' => [
                                        'avatarUrl' => ['stringValue' => $avatarUrl],
                                        'name' => ['stringValue' => $name],
                                    ],
                                ],
                            ],
                        ],
                    ],
                );
                $patched++;
            }
        } catch (\Throwable $e) {
            Log::warning("FirestoreMomentService::backfillAuthorInfo({$uid}) threw: {$e->getMessage()}");
        }

        return $patched;
    }

    private function accessToken(): string
    {
        return Cache::remember('firestore_access_token', now()->addMinutes(50), function () {
            $path = $this->resolvedCredentialsPath();
            if (!$path || !file_exists($path)) {
                throw new RuntimeException(
                    'Firebase credentials not found — see FIREBASE_CREDENTIALS in .env.'
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

    private function resolvedCredentialsPath(): ?string
    {
        if (!$this->credentialsPath) {
            return null;
        }

        $isAbsolute = str_starts_with($this->credentialsPath, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $this->credentialsPath) === 1;

        return $isAbsolute ? $this->credentialsPath : base_path($this->credentialsPath);
    }
}
