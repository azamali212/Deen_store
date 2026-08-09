<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class PasswordPreviouslyUsedException extends RuntimeException
{
    public static function create(): self
    {
        return new self(
            'You cannot reuse one of your recent passwords.',
        );
    }
}
