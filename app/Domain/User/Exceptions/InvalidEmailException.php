<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class InvalidEmailException extends DomainException
{
    public static function alreadyTaken(string $value): self
    {
        return (new self("Email '{$value}' is already in use."))
            ->withContext(['value' => $value]);
    }
}
