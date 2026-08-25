<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeepLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Short, shareable codes behind the QR-code / "Add Friend via link" flow:
 *
 *   Flutter (owner)  -> POST /api/deep-links      -> mint/reuse a code
 *   Flutter (scanner) -> GET  /api/deep-links/{code} -> resolve it back
 *   Browser (no app)  -> GET  /u/{code} (web)      -> DeepLinkRedirectController
 *
 * Public like NotificationController/MediaController: this app's real
 * identity layer is Firebase Auth, not Laravel Sanctum, so there's no
 * bearer token to require here — uid is just a caller-supplied string,
 * same trust model those already use. Nothing here can do more than point
 * at a Firebase uid that was already public the moment it went into a QR
 * code/link, so there's no new capability being handed out.
 */
class DeepLinkController extends Controller
{
    /**
     * `type` values this endpoint knows how to mint. A closed list on
     * purpose — an arbitrary caller-supplied type would otherwise let
     * anyone litter the table with junk rows.
     */
    private const TYPES = ['add_friend'];

    /**
     * Mints a code for (type, uid) — or, if this uid already has a live
     * code of that type, reuses it and refreshes its payload snapshot
     * instead of minting a new one. Keeps a user's "My QR Code" URL
     * permanent across app opens (same behavior the old pure-client-side
     * link had) while still going through the backend for a real short
     * code. Called from friend_link_service.dart.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:' . implode(',', self::TYPES)],
            'uid' => ['required', 'string', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
            'handle' => ['nullable', 'string', 'max:191'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $type = $request->input('type');
        $uid = $request->input('uid');
        $payload = array_filter([
            'name' => $request->input('name'),
            'handle' => $request->input('handle'),
            'avatarUrl' => $request->input('avatar_url'),
        ], fn ($value) => $value !== null && $value !== '');

        $deepLink = DeepLink::where('type', $type)->where('uid', $uid)->first();

        if ($deepLink && !$deepLink->isExpired()) {
            $deepLink->update(['payload' => $payload]);
        } else {
            $deepLink = DeepLink::create([
                'code' => DeepLink::generateUniqueCode(),
                'type' => $type,
                'uid' => $uid,
                'payload' => $payload,
            ]);
        }

        return response()->json(['success' => true, 'deepLink' => $this->present($deepLink)]);
    }

    /**
     * Resolves a scanned/tapped code. Used by both the Flutter app (Case A
     * — app already installed, deep_link_service.dart) and indirectly by
     * DeepLinkRedirectController (Case B's landing page reads the same
     * row directly rather than looping back through HTTP).
     */
    public function show(string $code): JsonResponse
    {
        $deepLink = DeepLink::where('code', $code)->first();

        if (!$deepLink || $deepLink->isExpired()) {
            return response()->json(['success' => false, 'message' => 'This link has expired or is invalid.'], 404);
        }

        $deepLink->increment('clicks');

        return response()->json(['success' => true, 'deepLink' => $this->present($deepLink)]);
    }

    private function present(DeepLink $deepLink): array
    {
        return [
            'code' => $deepLink->code,
            'type' => $deepLink->type,
            'uid' => $deepLink->uid,
            'payload' => $deepLink->payload ?? [],
        ];
    }
}
