<?php

namespace App\Http\Controllers;

use App\Models\DeepLink;
use Illuminate\View\View;

/**
 * The "app not installed" branch of the QR-code flow (Case B in the deep
 * link flow doc). A real Android App Link / iOS Universal Link tap never
 * reaches this at all when the app IS installed and verified — the OS
 * hands the URL straight to Flutter instead (see deep_link_service.dart).
 * This only renders when there's no app to intercept it: a browser, an
 * unverified device, or a device that never installed FaceTalk.
 */
class DeepLinkRedirectController extends Controller
{
    public function show(string $code): View
    {
        $deepLink = DeepLink::where('code', $code)->first();

        if ($deepLink && !$deepLink->isExpired()) {
            $deepLink->increment('clicks');
        }

        return view('deep-link', [
            'code' => $code,
            'deepLink' => $deepLink && !$deepLink->isExpired() ? $deepLink : null,
        ]);
    }
}
