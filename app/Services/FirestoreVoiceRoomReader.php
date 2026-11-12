<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only lookups of a voice room's Firestore state, used to decide what
 * audio permissions a caller gets (see VoiceRoomController::token).
 *
 * Same REST + service-account approach as FirestoreVipService, for the same
 * reason: no heavy gRPC client library for a couple of document reads.
 */
class FirestoreVoiceRoomReader
{
    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-52f9b');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    /** Role string ('host'|'moderator'|'speaker'|'listener') or null when not in the room. */
    public function participantRole(string $roomId, string $uid): ?string
    {
        $doc = $this->get("voiceRooms/{$roomId}/participants/{$uid}");

        return $doc['fields']['role']['stringValue'] ?? null;
    }

    public function isRoomActive(string $roomId): bool
    {
        $doc = $this->get("voiceRooms/{$roomId}");

        return (bool) ($doc['fields']['isActive']['booleanValue'] ?? false);
    }

    private function get(string $path): ?array
    {
        $response = Http::withToken($this->accessToken())
            ->get("https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$path}");

        if ($response->status() === 404) {
            return null;
        }
        if (! $response->successful()) {
            throw new RuntimeException("Firestore read failed for {$path}: " . $response->body());
        }

        return $response->json();
    }

    private function accessToken(): string
    {
        return Cache::remember('firestore_access_token', now()->addMinutes(50), function () {
            $path = $this->resolvedCredentialsPath();
            if (! $path || ! file_exists($path)) {
                throw new RuntimeException('Firebase credentials not found — see FIREBASE_CREDENTIALS in .env.');
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/datastore',
                $path,
            );

            return $credentials->fetchAuthToken()['access_token'];
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
        if (! $this->credentialsPath) {
            return null;
        }

        $isAbsolute = str_starts_with($this->credentialsPath, '/')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $this->credentialsPath) === 1;

        return $isAbsolute ? $this->credentialsPath : base_path($this->credentialsPath);
    }
}
