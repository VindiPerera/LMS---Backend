<?php

namespace App\Services;

use Firebase\JWT\JWT;
use RuntimeException;

/**
 * Mints LiveKit access tokens (a plain HS256 JWT signed with the project's
 * API secret) so the Flutter app can join a voice room's audio session.
 *
 * Done by hand with firebase/php-jwt — already installed as a dependency of
 * kreait/laravel-firebase — rather than pulling in a LiveKit server SDK for
 * what is one signed JSON payload.
 *
 * The LiveKit room name is the Firestore voiceRooms/{roomId} id, so the two
 * stay one-to-one with no mapping table.
 */
class LiveKitTokenService
{
    /**
     * 6 hours — LiveKit's own default. LiveKit's SDK re-presents this same
     * token when it silently resumes a dropped connection, so a short TTL
     * (it was 10 minutes) made any network blip after that point fail to
     * reconnect and left the room silent. Access is still enforced per
     * request: the endpoint only mints for current, non-removed
     * participants, and a seat change re-mints with the new publish grant.
     */
    private const TTL_SECONDS = 21600;

    public function url(): string
    {
        return (string) config('services.livekit.url');
    }

    /**
     * @param bool $canPublish true for host/moderator/speaker seats, false for
     *                         listeners — enforced by the LiveKit server, so
     *                         a tampered client still can't open its mic.
     */
    public function mint(string $roomId, string $uid, string $name, bool $canPublish): string
    {
        $key = config('services.livekit.api_key');
        $secret = config('services.livekit.api_secret');

        if (! $key || ! $secret) {
            throw new RuntimeException('LiveKit is not configured — set LIVEKIT_API_KEY / LIVEKIT_API_SECRET in .env.');
        }

        $now = time();

        return JWT::encode([
            'iss' => $key,
            'sub' => $uid,
            'name' => $name,
            'nbf' => $now,
            'exp' => $now + self::TTL_SECONDS,
            'video' => [
                'room' => $roomId,
                'roomJoin' => true,
                'canSubscribe' => true,
                'canPublish' => $canPublish,
                'canPublishData' => false,
                'canPublishSources' => $canPublish ? ['microphone'] : [],
            ],
        ], $secret, 'HS256');
    }
}
