<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\DeepLinkRedirectController;
use App\Http\Controllers\WellKnownController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Explicit storage and media delivery routes with CORS headers (for Flutter Web and Mobile)
Route::get('/storage/{path}', [MediaController::class, 'serveFile'])->where('path', '.*');
Route::get('/media/file/{path}', [MediaController::class, 'serveFile'])->where('path', '.*');

// QR code / share-link landing page — Case B (app not installed) of the
// deep link flow. Case A (app installed + verified) never reaches this;
// the OS hands the URL straight to Flutter instead. See
// DeepLinkRedirectController's class doc.
Route::get('/u/{code}', [DeepLinkRedirectController::class, 'show'])->name('deep-link.show');

// Android App Links / iOS Universal Links verification files — see
// WellKnownController's class doc for why these are routes, not static
// files under public/.well-known/.
Route::get('/.well-known/assetlinks.json', [WellKnownController::class, 'assetLinks']);
Route::get('/.well-known/apple-app-site-association', [WellKnownController::class, 'appleAppSiteAssociation']);

// Admin panel (Blade + session auth, its own `admin` guard) — see routes/admin.php.
require __DIR__.'/admin.php';
