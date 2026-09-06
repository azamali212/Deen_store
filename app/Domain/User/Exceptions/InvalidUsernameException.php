<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class InvalidUsernameException extends DomainException
{
    public static function tooShort(string $value): self
    {
        return (new self("Username '{$value}' is too short."))
            ->withContext(['value' => $value]);
    }

    public static function tooLong(string $value): self
    {
        return (new self("Username '{$value}' exceeds maximum allowed length."))
            ->withContext(['value' => $value]);
    }

    public static function invalidFormat(string $value): self
    {
        return (new self("Username '{$value}' contains invalid characters."))
            ->withContext(['value' => $value]);
    }
}
