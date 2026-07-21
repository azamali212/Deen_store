<?php

declare(strict_types=1);

return [

    'login' => [
        'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 1),
        'decay_seconds' => env('LOGIN_DECAY_SECONDS', 9),
    ],

    'lockout' => [
        'max_failed_attempts' => env('ACCOUNT_LOCKOUT_MAX_ATTEMPTS', 2),
        'duration_minutes' => env('ACCOUNT_LOCKOUT_DURATION_MINUTES', 60),
    ],
];