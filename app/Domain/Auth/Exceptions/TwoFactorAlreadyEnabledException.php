<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class TwoFactorAlreadyEnabledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Two-factor authentication is already enabled.',
        );
    }
}