<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class InvalidPhoneException extends DomainException
{
    public static function invalidFormat(string $value): self
    {
        return (new self("Phone number '{$value}' is not a valid format."))
            ->withContext(['value' => $value]);
    }

    public static function alreadyTaken(string $value): self
    {
        return (new self("Phone number '{$value}' is already in use."))
            ->withContext(['value' => $value]);
    }
}
