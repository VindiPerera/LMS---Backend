<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Posts the admin panel's broadcast into every matched user's chat list as
 * a message from "FaceTalk" — same `chats/{chatId}` + `messages`
 * documents ChatService.sendMessage (hello-frontend) writes, so it renders
 * in the app's normal chat UI rather than a separate feature. The uid on
 * the "FaceTalk" side is fixed (self::SYSTEM_UID) and must match
 * ChatService.systemUid on the Flutter side exactly — that's what makes
 * chat_detail_screen.dart hide the reply composer for it, and what
 * firestore.rules' `isReadOnly` check (set on the chat doc here) blocks a
 * reply through even if someone bypassed the UI.
 *
 * Uses the Firestore REST API's batched :commit endpoint — same
 * REST-over-gRPC approach as FirestoreVipService/FirestoreUserDirectory —
 * so up to 250 recipients' chat+message writes (2 each, the :commit cap is
 * 500 writes) land in one HTTP call instead of one round-trip per user.
 */
class FirestoreChatBroadcastService
{
    /**
     * Must match ChatService.systemUid in hello-frontend/lib/services/chat_service.dart.
     */
    public const SYSTEM_UID = 'facetalk_system';

    private const CHUNK_SIZE = 250;

    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-82bf9');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    /**
     * Posts one "FaceTalk" message into each of $uids' chat thread.
     * $uids may be any iterable (a generator is fine — chunked internally).
     */
    public function sendToMany(iterable $uids, string $body): void
    {
        $chunk = [];
        foreach ($uids as $uid) {
            $chunk[] = $uid;
            if (count($chunk) >= self::CHUNK_SIZE) {
                $this->sendBatch($chunk, $body);
                $chunk = [];
            }
        }
        if ($chunk) {
            $this->sendBatch($chunk, $body);
        }
    }

    private function sendBatch(array $uids, string $body): void
    {
        $now = ['timestampValue' => now('UTC')->format('Y-m-d\TH:i:s.u\Z')];
        $writes = [];

        foreach ($uids as $uid) {
            $participants = $this->participantsFor($uid);
            $chatId = implode('_', $participants);
            $messageId = (string) Str::ulid();

            $writes[] = [
                'update' => [
                    'name' => $this->documentName("chats/{$chatId}"),
                    'fields' => [
                        'participants' => $this->encodeValue($participants),
                        'participantInfo' => $this->encodeValue([
                            self::SYSTEM_UID => [
                                'name' => 'FaceTalk',
                                'avatarUrl' => '',
                                'countryFlag' => '',
                                'handle' => 'facetalk',
                            ],
                        ]),
                        'lastMessage' => $this->encodeValue($body),
                        'lastMessageAt' => $now,
                        'lastMessageSenderId' => $this->encodeValue(self::SYSTEM_UID),
                        'unread' => $this->encodeValue([$uid => 1, self::SYSTEM_UID => 0]),
                        'isReadOnly' => $this->encodeValue(true),
                    ],
                ],
                'updateMask' => ['fieldPaths' => [
                    'participants',
                    'participantInfo.'.self::SYSTEM_UID,
                    'lastMessage',
                    'lastMessageAt',
                    'lastMessageSenderId',
                    'unread.'.$uid,
                    'unread.'.self::SYSTEM_UID,
                    'isReadOnly',
                ]],
            ];

            // Brand-new document each time — no updateMask, full document.
            $writes[] = [
                'update' => [
                    'name' => $this->documentName("chats/{$chatId}/messages/{$messageId}"),
                    'fields' => [
                        'senderId' => $this->encodeValue(self::SYSTEM_UID),
                        'text' => $this->encodeValue($body),
                        'type' => $this->encodeValue('text'),
                        'createdAt' => $now,
                        'participants' => $this->encodeValue($participants),
                    ],
                ],
            ];
        }

        $response = Http::withToken($this->accessToken())
            ->post($this->baseUrl().':commit', ['writes' => $writes]);

        if (!$response->successful()) {
            throw new RuntimeException('Firestore chat broadcast commit failed: '.$response->body());
        }
    }

    /**
     * [uid, SYSTEM_UID] sorted — must match ChatService.chatIdFor's
     * algorithm (and firestore.rules' idMatchesParticipants) exactly.
     */
    private function participantsFor(string $uid): array
    {
        $ids = [$uid, self::SYSTEM_UID];
        sort($ids);

        return $ids;
    }

    private function documentName(string $path): string
    {
        return "projects/{$this->projectId}/databases/(default)/documents/{$path}";
    }

    private function baseUrl(): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
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
            is_array($value) && array_is_list($value) => ['arrayValue' => ['values' => array_map(fn ($v) => $this->encodeValue($v), $value)]],
            is_array($value) => ['mapValue' => ['fields' => collect($value)->map(fn ($v) => $this->encodeValue($v))->all()]],
            default => ['stringValue' => (string) $value],
        };
    }
}
