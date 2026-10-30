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

    'telegram' => [
        'api_id'   => env('TELEGRAM_API_ID'),
        'api_hash' => env('TELEGRAM_API_HASH'),
        'session'  => env('TELEGRAM_SESSION_PATH', 'telegram/session.madeline'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'razorpay' => [
        'key_id'         => env('RAZORPAY_KEY_ID', 'rzp_test_placeholder'),
        'key_secret'     => env('RAZORPAY_KEY_SECRET', 'test_secret_placeholder'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'test_webhook_secret_placeholder'),
        'currency'       => env('RAZORPAY_CURRENCY', 'INR'),
    ],

];
