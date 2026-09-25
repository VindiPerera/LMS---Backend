<?php

namespace App\Services;

use App\Support\Agora\RtcTokenBuilder2;
use RuntimeException;

require_once __DIR__.'/../Support/Agora/Util.php';
require_once __DIR__.'/../Support/Agora/AccessToken2.php';
require_once __DIR__.'/../Support/Agora/RtcTokenBuilder2.php';

/**
 * Mints short-lived Agora RTC tokens for the voice room's live audio. The
 * signing itself is Agora's own official builder, vendored unmodified
 * (aside from a namespace) under app/Support/Agora — see the README there.
 *
 * The app's Firebase uid is a string; Agora's numeric-uid tokens want a
 * 32-bit unsigned int, so [agoraUid] derives a stable one from the uid
 * (crc32, masked to 31 bits so it stays positive on every SDK). The same
 * derivation must be used anywhere an Agora uid needs mapping back to a
 * Firebase uid (e.g. speaking indicators) — a collision inside one room
 * would need two of its members to share a crc32, roughly 1 in 2 billion
 * per pair, which is acceptable for v1.
 */
class AgoraTokenService
{
    /**
     * @return array{app_id: string, channel: string, uid: int, role: string, token: string, expires_at: int}
     */
    public function buildRtcToken(string $channel, string $firebaseUid, bool $publisher): array
    {
        $appId = (string) config('services.agora.app_id');
        $certificate = (string) config('services.agora.app_certificate');
        if ($appId === '' || $certificate === '') {
            throw new RuntimeException('Agora is not configured — set AGORA_APP_ID and AGORA_APP_CERTIFICATE.');
        }

        $ttl = max(60, (int) config('services.agora.token_ttl', 3600));
        $uid = $this->agoraUid($firebaseUid);

        $token = RtcTokenBuilder2::buildTokenWithUid(
            $appId,
            $certificate,
            $channel,
            $uid,
            $publisher ? RtcTokenBuilder2::ROLE_PUBLISHER : RtcTokenBuilder2::ROLE_SUBSCRIBER,
            $ttl,
            $ttl,
        );

        // Agora's builder returns "" (rather than throwing) when the App ID
        // or Certificate isn't a 32-char hex string — usually a stray space
        // or quote pasted into .env. Fail loudly instead of handing the app
        // a token that can never work.
        if ($token === '') {
            throw new RuntimeException('AGORA_APP_ID / AGORA_APP_CERTIFICATE are malformed — each must be a 32-character hex string.');
        }

        return [
            'app_id' => $appId,
            'channel' => $channel,
            'uid' => $uid,
            'role' => $publisher ? 'publisher' : 'audience',
            'token' => $token,
            'expires_at' => time() + $ttl,
        ];
    }

    public function agoraUid(string $firebaseUid): int
    {
        $uid = crc32($firebaseUid) & 0x7FFFFFFF;

        return $uid === 0 ? 1 : $uid;
    }
}
