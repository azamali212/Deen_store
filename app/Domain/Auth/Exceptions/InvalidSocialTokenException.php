<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

final class InvalidSocialTokenException extends AuthException
{
    public static function invalid(): self
    {
        return new self(
            'The Google sign-in token is invalid or has expired. Please try signing in again.',
            401,
        );
    }
}
