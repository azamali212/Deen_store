<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class InvalidTwoFactorCodeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The provided two-factor authentication code is invalid.',
        );
    }
}