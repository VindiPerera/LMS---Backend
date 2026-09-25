<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only access to a voice room's own Firestore documents
 * (`voiceRooms/{roomId}` and `voiceRooms/{roomId}/participants/{uid}`) —
 * used by VoiceRoomTokenController to decide, server-side, whether a caller
 * may get an audio token for a room and whether it may publish. Same REST +
 * service-account approach as FirestoreUserDirectory (duplicated on purpose,
 * per that class's own note, rather than shared).
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

    public function room(string $roomId): ?array
    {
        return $this->getDocument('voiceRooms/'.rawurlencode($roomId));
    }

    public function participant(string $roomId, string $uid): ?array
    {
        return $this->getDocument('voiceRooms/'.rawurlencode($roomId).'/participants/'.rawurlencode($uid));
    }

    private function getDocument(string $path): ?array
    {
        $response = Http::withToken($this->accessToken())->get(
            "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/{$path}"
        );

        if ($response->status() === 404) {
            return null;
        }
        if (!$response->successful()) {
            throw new RuntimeException('Failed to read Firestore document: '.$response->body());
        }

        return $this->decodeFields($response->json('fields') ?? []);
    }

    private function accessToken(): string
    {
        return Cache::remember('firestore_access_token', now()->addMinutes(50), function () {
            if (!$this->credentialsPath || !file_exists(base_path($this->credentialsPath))) {
                throw new RuntimeException(
                    'Firebase credentials not found — see FIREBASE_CREDENTIALS in .env.'
                );
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/datastore',
                base_path($this->credentialsPath),
            );

            return $credentials->fetchAuthToken()['access_token'];
        });
    }

    private function decodeFields(array $fields): array
    {
        return collect($fields)->map(fn ($value) => $this->decodeValue($value))->all();
    }

    private function decodeValue(array $value): mixed
    {
        return match (true) {
            array_key_exists('stringValue', $value) => $value['stringValue'],
            array_key_exists('booleanValue', $value) => $value['booleanValue'],
            array_key_exists('integerValue', $value) => (int) $value['integerValue'],
            array_key_exists('doubleValue', $value) => (float) $value['doubleValue'],
            array_key_exists('timestampValue', $value) => $value['timestampValue'],
            array_key_exists('nullValue', $value) => null,
            array_key_exists('arrayValue', $value) => array_map(
                fn ($v) => $this->decodeValue($v),
                $value['arrayValue']['values'] ?? [],
            ),
            array_key_exists('mapValue', $value) => $this->decodeFields($value['mapValue']['fields'] ?? []),
            default => null,
        };
    }
}
