<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Serves the two files Android App Links / iOS Universal Links require to
 * prove this domain and the FaceTalk app are the same publisher — without
 * these, tapping an https://.../u/{code} link (or scanning its QR code)
 * always opens a browser, even with the app installed (Case A in the deep
 * link flow doc silently degrades to Case B).
 *
 * Served through routes instead of static files under public/.well-known/
 * because many default web server configs (Apache's mod_dir, some Nginx
 * setups) block dot-directories outright — a route can't be blocked that
 * way and guarantees the right Content-Type regardless of hosting.
 */
class WellKnownController extends Controller
{
    /**
     * Android: proves this domain may open links in the app signed by the
     * listed certificate(s). See https://developer.android.com/training/app-links/verify-android-applinks
     *
     * NOTE: the fingerprint below is the DEBUG keystore's — see
     * android/app/build.gradle.kts, which currently signs release builds
     * with the debug config too (`signingConfig = signingConfigs.getByName("debug")`,
     * marked TODO there). It's accurate for the app as it ships today, but
     * once a real release keystore is set up, regenerate this fingerprint
     * with:
     *   keytool -list -v -keystore <release>.jks -alias <alias>
     * and swap it in below (both can be listed at once during rollover).
     */
    public function assetLinks(): JsonResponse
    {
        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => 'com.jaan.FaceTalk_clone',
                    'sha256_cert_fingerprints' => [
                        '6C:46:5B:77:83:15:6E:BC:37:99:6D:21:1B:CA:E1:2A:E1:96:B3:3F:23:CE:1C:50:E3:2B:9A:88:0D:E9:CC:28',
                    ],
                ],
            ],
        ]);
    }

    /**
     * iOS: proves this domain may open links in the app. See
     * https://developer.apple.com/documentation/xcode/supporting-universal-links-in-your-app
     *
     * TEAMID is a placeholder — this project has no Apple Developer Team
     * ID yet (no Xcode signing has ever been configured here; see
     * ios/Runner.xcodeproj, which has no DEVELOPMENT_TEAM set). Replace
     * "TEAMID" with the real 10-character Team ID from
     * developer.apple.com/account once one exists, and add the
     * `applinks:hellotalk.jaan.lk` associated domain + this file's
     * appID in Xcode's Signing & Capabilities tab.
     */
    public function appleAppSiteAssociation(): JsonResponse
    {
        return response()->json([
            'applinks' => [
                'apps' => [],
                'details' => [
                    [
                        'appID' => 'TEAMID.com.jaan.FaceTalkClone',
                        'paths' => ['/u/*'],
                    ],
                ],
            ],
        ]);
    }
}
