<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

final class SellerProfileAlreadyExistsException extends DomainException
{
    public static function forUser(int $userId): self
    {
        return (new self("A seller profile already exists for user ID: {$userId}"))
            ->withContext(['user_id' => $userId]);
    }
}
