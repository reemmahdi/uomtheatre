<?php

return [

    'arqam' => [
        'key' => env('ARQAM_API_KEY'),
        'project' => env('ARQAM_PROJECT'),
        'template' => env('ARQAM_TEMPLATE'),
    ],

'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'allowed_client_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_ALLOWED_CLIENT_IDS', ''))))),
    ],
    
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

];
