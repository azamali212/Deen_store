<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class TooManyLoginAttemptsException extends RuntimeException
{
    public function __construct(
        public readonly int $secondsUntilAvailable,
    ) {
        parent::__construct(
            'Too many login attempts. Please try again later.'
        );
    }
}