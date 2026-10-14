<?php

return [
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'endpoint' => env(
            'GEMINI_ENDPOINT',
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'
        ),
    ],

    'elevenlabs' => ['key' => env('ELEVENLABS_API_KEY')],

    'fcm' => ['server_key' => env('FCM_SERVER_KEY')],

    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
];
