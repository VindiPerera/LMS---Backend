<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // "Continue with Google": web client ID from Google Cloud Console
    // (APIs & Services > Credentials > OAuth client ID > Web application).
    // The Flutter web app authenticates with Google Identity Services using
    // this same client ID, then sends us the resulting ID token to verify.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

    // Direct card payments (PaymentController/PayPalService) — from
    // developer.paypal.com -> Apps & Credentials, under the SAME PayPal
    // Business account that has "Advanced Credit and Debit Card Payments"
    // enabled. Sandbox and live have separate credentials; PAYPAL_MODE
    // picks which base URL gets used.
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],

    // Used by FirestoreVipService/FirestoreUserDirectory to build the
    // Firestore REST API URL — matches hello-frontend/lib/
    // firebase_options.dart's projectId. (Was 'hello-82bf9' — a different,
    // unrelated Firebase project a service-account key had apparently been
    // generated for at some point; fixed to the project the app actually
    // uses.)
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'hello-52f9b'),
    ],

    // Live voice rooms — see AgoraTokenService/VoiceRoomTokenController.
    // app_id is public (returned to the app in the token response so it's
    // never hardcoded client-side); app_certificate is the SECRET used to
    // sign tokens and must never leave this server.
    'agora' => [
        'app_id' => env('AGORA_APP_ID'),
        'app_certificate' => env('AGORA_APP_CERTIFICATE'),
        'token_ttl' => (int) env('AGORA_TOKEN_TTL', 3600),
    ],

];
