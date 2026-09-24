<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 409 — a user can only ever have one application (UNIQUE user_id, D2).
final class SellerApplicationAlreadyExistsException extends DomainException
{
    public static function forUser(int $userId): self
    {
        return (new self('You already have a seller application. Edit or resubmit it instead of creating a new one.'))
            ->withContext(['user_id' => $userId]);
    }
}
