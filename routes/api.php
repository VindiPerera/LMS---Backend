<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeepLinkController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Support\Facades\Route;

// Public auth endpoints
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Media upload and streaming endpoints (Moments images, videos, thumbnails, avatars)
Route::post('/media/upload', [MediaController::class, 'upload']);
Route::get('/media/file/{path}', [MediaController::class, 'serveFile'])->where('path', '.*');
Route::get('/media/{id}', [MediaController::class, 'show']);

// Push notifications (replaces the Cloud Functions -> Firestore pipeline
// this project doesn't deploy — see NotificationController's class doc).
// Public like the media endpoints above: the app's real identity layer is
// Firebase Auth, not Laravel Sanctum, so there's no bearer token here to
// require — the uid is just a caller-supplied string, same trust model the
// media endpoints already use.
Route::post('/fcm-token', [NotificationController::class, 'saveToken']);
Route::post('/notifications/push', [NotificationController::class, 'sendPush']);

// Direct card payments (PayPal Advanced Card Payments) — see
// PaymentController's class doc.
Route::post('/payments/card-pay', [PaymentController::class, 'payWithCard']);

// QR code / share-link deep links (see DeepLinkController's class doc and
// deep_link_service.dart / friend_link_service.dart on the Flutter side).
// Public for the same reason as fcm-token/notifications above.
Route::post('/deep-links', [DeepLinkController::class, 'store']);
Route::get('/deep-links/{code}', [DeepLinkController::class, 'show']);


// Authenticated endpoints (Sanctum bearer token required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/partners', [PartnerController::class, 'index']);
    Route::get('/partners/{user}', [PartnerController::class, 'show']);
});

