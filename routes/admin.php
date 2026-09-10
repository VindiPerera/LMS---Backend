<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Admin panel — plain Blade + session auth on the `admin` guard, isolated
// from both the JSON API (routes/api.php) and the mobile app's Sanctum
// auth. Included from web.php so it inherits the `web` middleware group
// (session, cookies, CSRF).
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // {uid} is a Firebase Auth uid (string), not an Eloquent model id —
        // see FirestoreUserDirectory's doc for why.
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{uid}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{uid}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{uid}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('/users/{uid}/unban', [UserController::class, 'unban'])->name('users.unban');
        Route::post('/users/{uid}/force-logout', [UserController::class, 'forceLogout'])->name('users.force-logout');
        Route::post('/users/{uid}/reset-password', [UserController::class, 'sendPasswordReset'])->name('users.reset-password');

        Route::get('/broadcasts', [BroadcastController::class, 'index'])->name('broadcasts.index');
        Route::get('/broadcasts/create', [BroadcastController::class, 'create'])->name('broadcasts.create');
        Route::post('/broadcasts/preview', [BroadcastController::class, 'preview'])->name('broadcasts.preview');
        Route::post('/broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store');
    });
});
