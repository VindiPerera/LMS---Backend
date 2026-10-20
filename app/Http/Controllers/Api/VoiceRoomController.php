<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreVoiceRoomReader;
use App\Services\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Throwable;

/**
 * Hands the Flutter app a LiveKit token for a voice room's live audio.
 *
 * Unlike the other public endpoints here (fcm-token, notifications/push),
 * this one authenticates the caller: the app's identity layer is Firebase
 * Auth, so the request carries the user's Firebase ID token as a bearer
 * token and we verify it with the Admin SDK rather than trusting a
 * caller-supplied uid. Publish rights then come from the user's *current*
 * role in Firestore (voiceRooms/{roomId}/participants/{uid}.role) — host,
 * moderator and speaker may talk, listeners may only listen.
 *
 * A role change (listener -> speaker, or a demotion) takes effect when the
 * client fetches a fresh token, which voice_room_audio_service.dart does
 * on every role change.
 */
class VoiceRoomController extends Controller
{
    private const SEATED_ROLES = ['host', 'moderator', 'speaker'];

    public function token(
        Request $request,
        string $roomId,
        FirebaseAuth $auth,
        FirestoreVoiceRoomReader $rooms,
        LiveKitTokenService $livekit,
    ): JsonResponse {
        $idToken = $request->bearerToken();
        if (! $idToken) {
            return response()->json(['success' => false, 'message' => 'Missing Firebase ID token.'], 401);
        }

        try {
            $verified = $auth->verifyIdToken($idToken);
            $uid = (string) $verified->claims()->get('sub');
            $name = (string) ($verified->claims()->get('name') ?? '');
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid Firebase ID token.'], 401);
        }

        try {
            if (! $rooms->isRoomActive($roomId)) {
                return response()->json(['success' => false, 'message' => 'This room has ended.'], 404);
            }

            // Not in the room (never joined, or removed/banned) -> no audio.
            $role = $rooms->participantRole($roomId, $uid);
            if ($role === null) {
                return response()->json(['success' => false, 'message' => 'Join the room first.'], 403);
            }

            return response()->json([
                'success' => true,
                'url' => $livekit->url(),
                'token' => $livekit->mint($roomId, $uid, $name, in_array($role, self::SEATED_ROLES, true)),
                'canPublish' => in_array($role, self::SEATED_ROLES, true),
            ]);
        } catch (Throwable $e) {
            Log::error('Voice room token failed', ['room' => $roomId, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Could not start voice.'], 500);
        }
    }
}
