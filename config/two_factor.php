<?php

declare(strict_types=1);

return [

    'provider' => 'google',

    'digits' => 6,

    'window' => 1,

    'recovery_codes' => 8,

    'issuer' => env(
        'APP_NAME',
        'Laravel',
    ),

];