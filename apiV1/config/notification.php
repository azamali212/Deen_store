<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Available notification channels for the system.
    |
    */
    'channels' => ['database', 'mail', 'broadcast'],

    /*
    |--------------------------------------------------------------------------
    | Alert Channels
    |--------------------------------------------------------------------------
    |
    | Additional alert channels for urgent notifications.
    |
    */
    'alerts' => ['slack', 'sms'],

    /*
    |--------------------------------------------------------------------------
    | Critical Permissions
    |--------------------------------------------------------------------------
    |
    | Permissions that trigger high-priority notifications.
    |
    */
    'critical_permissions' => [
        'user-delete',
        'role-delete',
        'permission-delete',
        'system-config',
        'admin-access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Time-based settings for notifications.
    |
    */
    'settings' => [
        'reminder_hours' => 24,
        'escalation_hours' => 48,
        'cache_time' => 300, // 5 minutes
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    |
    | Email template settings.
    |
    */
    'email' => [
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'Role Approval System'),
        'reply_to' => env('MAIL_REPLY_TO', 'support@example.com'),
    ],
];