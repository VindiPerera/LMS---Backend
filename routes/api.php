<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PartnerController;
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


// Authenticated endpoints (Sanctum bearer token required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::get('/partners', [PartnerController::class, 'index']);
    Route::get('/partners/{user}', [PartnerController::class, 'show']);
});

