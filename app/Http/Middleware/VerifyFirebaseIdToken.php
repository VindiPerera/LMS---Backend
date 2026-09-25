<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Throwable;

/**
 * Authenticates a mobile-app request by its Firebase ID token
 * (`Authorization: Bearer <idToken>`) — the app's real identity layer is
 * Firebase Auth, not Sanctum, and unlike the app's older public endpoints
 * (fcm-token, deep-links...) this one must NOT trust a caller-supplied uid:
 * it gates who may join a room's live audio. The verified uid is exposed as
 * the `firebase_uid` request attribute.
 *
 * checkIfRevoked = true, so an account banned or force-logged-out from the
 * admin panel (which revokes its refresh tokens) is rejected immediately
 * rather than staying valid for the rest of its ~1h ID token lifetime.
 */
class VerifyFirebaseIdToken
{
    public function __construct(private readonly FirebaseAuth $firebaseAuth)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $idToken = $request->bearerToken();
        if (!$idToken) {
            return $this->unauthorized('Missing bearer token.');
        }

        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken, true);
            $uid = (string) $verified->claims()->get('sub');
        } catch (Throwable) {
            return $this->unauthorized('Invalid or expired token.');
        }

        if ($uid === '') {
            return $this->unauthorized('Invalid token.');
        }

        $request->attributes->set('firebase_uid', $uid);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 401);
    }
}
