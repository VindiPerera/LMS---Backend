<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AgoraTokenService;
use App\Services\FirestoreVoiceRoomReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Issues the Agora token a client needs to join a voice room's live audio.
 *
 * Authorization is decided here, from Firestore, never from anything the
 * client claims: the caller (verified Firebase uid — see
 * VerifyFirebaseIdToken) must already be a participant of an active room
 * (the app calls RoomParticipantService.join first, and Firestore's own
 * rules already refuse a banned user's join), and gets a publisher token
 * only while their participant role is host/moderator/speaker — everyone
 * else gets a subscribe-only token. Clients re-request after a role change
 * (e.g. raised hand accepted) to pick up publish rights.
 *
 * Caveat, per Agora's own docs: a subscriber-role token only truly cannot
 * publish once "co-host token authentication" is enabled for the project
 * (Agora support ticket) — until then Agora treats it like a publisher
 * token, so the listener/speaker split is enforced client-side only.
 */
class VoiceRoomTokenController extends Controller
{
    private const PUBLISHER_ROLES = ['host', 'moderator', 'speaker'];

    public function __construct(
        private readonly AgoraTokenService $tokens,
        private readonly FirestoreVoiceRoomReader $rooms,
    ) {
    }

    public function issue(Request $request, string $roomId): JsonResponse
    {
        // Firestore auto-ids are 20 chars; this also keeps the value safe as
        // both a URL path segment and an Agora channel name (<= 64 bytes).
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $roomId)) {
            return response()->json(['message' => 'Invalid room id.'], 422);
        }

        $uid = (string) $request->attributes->get('firebase_uid');

        try {
            $room = $this->rooms->room($roomId);
            if ($room === null) {
                return response()->json(['message' => 'Room not found.'], 404);
            }
            if (($room['isActive'] ?? true) === false) {
                return response()->json(['message' => 'This room has ended.'], 410);
            }

            $participant = $this->rooms->participant($roomId, $uid);
            if ($participant === null) {
                return response()->json(['message' => 'Join the room before requesting audio.'], 403);
            }

            $publisher = in_array($participant['role'] ?? 'listener', self::PUBLISHER_ROLES, true);

            return response()->json($this->tokens->buildRtcToken($roomId, $uid, $publisher));
        } catch (RuntimeException $e) {
            Log::error('Voice room token request failed', ['room' => $roomId, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Live audio is temporarily unavailable.'], 503);
        }
    }
}
