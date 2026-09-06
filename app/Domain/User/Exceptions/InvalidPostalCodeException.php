<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class InvalidPostalCodeException extends DomainException
{
    public static function invalidFormat(string $value): self
    {
        return (new self("Postal code '{$value}' is not a valid format."))
            ->withContext(['value' => $value]);
    }
}
