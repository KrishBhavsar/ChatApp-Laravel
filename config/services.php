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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Metered TURN — used to mint SHORT-LIVED (ephemeral) ICE credentials so the
    // permanent secret never ships to the browser. 'app' is your Metered app
    // subdomain (e.g. "myapp" => myapp.metered.live); 'api_key' is the SECRET
    // API key from the Metered dashboard (Developers → API Key) — server-only.
    // 'static_*' are the long-lived fallback creds used if the API is down.
    'metered' => [
        'app' => env('METERED_APP'),
        'api_key' => env('METERED_API_KEY'),
        'static_username' => env('METERED_STATIC_USERNAME'),
        'static_credential' => env('METERED_STATIC_CREDENTIAL'),
    ],

];
