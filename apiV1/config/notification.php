<?php

declare(strict_types=1);

return [
    'api' => [
        'max_recipients_per_request' => (int) env('NOTIFICATION_MAX_RECIPIENTS', 200),
        'default_per_page' => (int) env('NOTIFICATION_DEFAULT_PER_PAGE', 20),
    ],

    'channels' => [
        'database' => [
            'enabled' => (bool) env('NOTIFICATION_DATABASE_ENABLED', true),
        ],
        'email' => [
            'enabled' => (bool) env('NOTIFICATION_EMAIL_ENABLED', true),
            'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'from_name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
        ],
        'broadcast' => [
            'enabled' => (bool) env('NOTIFICATION_BROADCAST_ENABLED', true),
            'event_name' => 'notification.received',
        ],
    ],

    'retry' => [
        'max_attempts' => (int) env('NOTIFICATION_RETRY_MAX_ATTEMPTS', 3),
        'base_backoff_seconds' => (int) env('NOTIFICATION_RETRY_BASE_BACKOFF', 5),
    ],

    'templates' => [
        'default_locale' => env('NOTIFICATION_DEFAULT_LOCALE', 'en'),
        'cache_ttl' => (int) env('NOTIFICATION_TEMPLATE_CACHE_TTL', 300),
    ],

    'queues' => [
        'high' => 'notifications-high',
        'default' => 'notifications-default',
        'email' => 'notifications-email',
        'realtime' => 'notifications-realtime',
    ],
];
