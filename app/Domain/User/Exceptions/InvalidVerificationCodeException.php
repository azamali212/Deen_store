<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class InvalidVerificationCodeException extends DomainException
{
    public static function forPhone(): self
    {
        return new self('The verification code is invalid or has expired.');
    }
}
