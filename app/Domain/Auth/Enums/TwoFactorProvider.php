<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

enum TwoFactorProvider: string
{
    case GOOGLE = 'google';

    case MICROSOFT = 'microsoft';

    case AUTHY = 'authy';
}