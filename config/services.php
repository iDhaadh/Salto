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

    /*
    | Meta WhatsApp Business Cloud API. Proactive alerts must use a
    | pre-approved message template (free-form messages require an open
    | 24h customer-service window, which does not apply to outbound alerts).
    */
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'template_low' => env('WHATSAPP_TEMPLATE_LOW', 'battery_low_alert'),
        'template_flat' => env('WHATSAPP_TEMPLATE_FLAT', 'battery_flat_alert'),
        'template_locale' => env('WHATSAPP_TEMPLATE_LOCALE', 'en'),
    ],

];
