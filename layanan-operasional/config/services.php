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

    'rabbitmq' => [
        'host' => env(
            'RABBITMQ_HOST',
            'host.docker.internal',
        ),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'jackrabbit'),
        'password' => env('RABBITMQ_PASSWORD', 'secret'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env(
            'RABBITMQ_EXCHANGE',
            'pesanan.events',
        ),
        'queue' => env(
            'RABBITMQ_QUEUE',
            'operasional.pesanan-dikonfirmasi',
        ),
        'routing_key' => env(
            'RABBITMQ_ROUTING_KEY',
            'pesanan.dikonfirmasi',
        ),
    ],

];
