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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'contact' => [
        'recipient' => env('MAIL_CONTACT_RECIPIENT', env('MAIL_FROM_ADDRESS')),
    ],

    // Public OAuth client ID for "Inloggen met Google" (not a secret).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', '573350916613-39cf2ifuv41uqsombrp4rvucu0k25f09.apps.googleusercontent.com'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORG'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    ],

];
