<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\DeepLinkRedirectController;
use App\Http\Controllers\WellKnownController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public website (React SPA in LMS-web/, built into public/web by
// `npm run build`). Every page path returns the same index.html and React
// Router picks the page client-side.
$serveWebApp = function () {
    $index = public_path('web/index.html');

    abort_unless(is_file($index), 503, 'Website not built yet — run `npm run build` in LMS-web/.');

    return response()->file($index, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'no-cache',
    ]);
};

Route::get('/', $serveWebApp);

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

// Any other GET path (/terms, /privacy, /refund, unknown pages) goes to the
// website so React Router can render it — except api/ and admin/, which keep
// their normal 404s.
Route::fallback(function (Request $request) use ($serveWebApp) {
    abort_if(
        ! $request->isMethod('GET') || $request->is('api/*', 'admin', 'admin/*'),
        404
    );

    return $serveWebApp();
});
