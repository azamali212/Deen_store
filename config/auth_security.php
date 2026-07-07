<?php

declare(strict_types=1);

return [
    
    'login' => [
        'max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => env('LOGIN_DECAY_SECONDS', 900),
    ],
];