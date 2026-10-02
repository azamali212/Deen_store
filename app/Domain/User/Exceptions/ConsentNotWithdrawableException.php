<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\User\Enums\ConsentType;
use App\Exceptions\DomainException;

final class ConsentNotWithdrawableException extends DomainException
{
    public static function forType(ConsentType $type): self
    {
        return (new self(sprintf(
            '"%s" cannot be withdrawn while the account exists. Deleting your account is how you withdraw it.',
            $type->label(),
        )))->withContext(['consent_type' => $type->value]);
    }
}
