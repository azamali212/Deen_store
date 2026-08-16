<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class InvalidRecoveryCodeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'The provided recovery code is invalid.',
        );
    }
}