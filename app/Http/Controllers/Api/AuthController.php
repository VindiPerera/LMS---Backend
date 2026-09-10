<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user (student or teacher) and return an API token.
     * Matches signup_screen.dart fields: email, password, role. The display
     * name is collected afterwards on create_profile_screen.dart, so a
     * placeholder derived from the email is used until then.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $name = $data['name'] ?? explode('@', $data['email'])[0];

        $user = User::create([
            'name' => $name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'handle' => $this->generateUniqueHandle($name),
        ]);

        $token = $user->createToken('facetalk-mobile')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Log in with email + password and return an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $this->assertNotBanned($user);

        $token = $user->createToken('facetalk-mobile')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * "Continue with Google": verify the ID token the Flutter client got from
     * Google Identity Services, then log the matching user in — creating a
     * new account on first sign-in — and return an API token.
     *
     * This mirrors register()/login() above but never touches a password.
     */
    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $payload = $this->verifyGoogleIdToken($request->validated('id_token'));

        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;

        $user = User::where('google_id', $googleId)->first();

        // First sign-in from a device, but the email already has an
        // account (e.g. they originally registered with a password) — link
        // the Google id to that existing account instead of duplicating it.
        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;
            $user = User::create([
                'name' => $payload['name'] ?? explode('@', $email ?? 'user')[0],
                'email' => $email,
                // Google-authenticated accounts don't have a usable password;
                // fill the (non-nullable at the DB layer for regular signups,
                // but nullable for these) column with an unguessable value.
                'password' => Hash::make(Str::random(40)),
                'role' => $request->validated('role') ?? 'student',
                'handle' => $this->generateUniqueHandle($payload['name'] ?? $email ?? 'user'),
                'avatar_url' => $payload['picture'] ?? null,
            ]);
        }

        if (! $user->google_id) {
            $user->google_id = $googleId;
        }
        if (! $user->email_verified_at && ($payload['email_verified'] ?? false)) {
            $user->email_verified_at = now();
        }
        $user->save();

        $this->assertNotBanned($user);

        $token = $user->createToken('facetalk-mobile')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
            'isNewUser' => $isNewUser,
        ], $isNewUser ? 201 : 200);
    }

    /**
     * Verify a Google-issued ID token via Google's tokeninfo endpoint and
     * return its decoded payload (sub, email, name, picture, ...).
     *
     * Uses the tokeninfo HTTP endpoint (rather than a JWKS/JWT library) to
     * keep this dependency-free; Google recommends it for low-volume,
     * server-side verification: https://developers.google.com/identity/sign-in/web/backend-auth
     */
    private function verifyGoogleIdToken(string $idToken): array
    {
        $clientId = config('services.google.client_id');

        if (! $clientId) {
            throw ValidationException::withMessages([
                'id_token' => ['Google Sign-In is not configured on this server (GOOGLE_CLIENT_ID is missing).'],
            ]);
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'id_token' => ['This Google sign-in could not be verified. Please try again.'],
            ]);
        }

        $payload = $response->json();

        // The token must have been issued for *this* app, not some other
        // client — otherwise anyone with a valid Google token for any app
        // could log in as that user here.
        if (($payload['aud'] ?? null) !== $clientId) {
            throw ValidationException::withMessages([
                'id_token' => ['This Google sign-in was issued for a different application.'],
            ]);
        }

        return $payload;
    }

    /**
     * "Forgot password" step 1: email the user a reset code (see
     * ResetPasswordNotification). Always returns success-shaped JSON even
     * for an unknown email, so callers can't enumerate registered emails.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'If that email has an account, a reset code has been sent to it.',
        ]);
    }

    /**
     * "Forgot password" step 2: consume the code from ResetPasswordNotification
     * and set a new password. Matches reset_password_screen.dart's fields.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                // Revoking existing tokens means a device that had this
                // account open before the reset needs to log in again.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }

        return response()->json(['message' => __($status)]);
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Reject login for an account an admin has banned (see the admin
     * panel's user management screen / App\Models\User::isBanned()).
     */
    private function assertNotBanned(User $user): void
    {
        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'email' => ['This account has been suspended. Contact support for help.'],
            ]);
        }
    }

    /**
     * Build a unique @handle from the user's display name, e.g. "Vinuk Lakvindu" -> "vinuk_lakvindu".
     */
    private function generateUniqueHandle(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'user';
        $handle = $base;
        $suffix = 1;

        while (User::where('handle', $handle)->exists()) {
            $handle = $base.'_'.$suffix++;
        }

        return $handle;
    }
}
