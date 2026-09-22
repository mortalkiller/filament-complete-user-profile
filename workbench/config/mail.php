<?php

declare(strict_types=1);

return [
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'demo@example.test'),
        'name' => env('MAIL_FROM_NAME', 'Complete User Profile Workbench'),
    ],
];
