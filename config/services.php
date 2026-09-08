<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    /*
    | External org user directory. Bulk list endpoint (POST, x-api-key header),
    | returns a bare array with encrypted ids. See docs/access-hub-requirements.md §8.
    */
    'user_api' => [
        'endpoint' => env('USER_API_ENDPOINT', ''),
        'key' => env('USER_API_KEY', ''),
    ],

    /*
    | Cloudflare Turnstile on the login form. Off unless TURNSTILE_VERIFY=true.
    */
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY', ''),
        'secret' => env('TURNSTILE_SECRET_KEY', ''),
        'verify' => env('TURNSTILE_VERIFY', false),
    ],

];
