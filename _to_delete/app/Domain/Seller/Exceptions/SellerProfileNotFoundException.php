<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

final class SellerProfileNotFoundException extends DomainException
{
    public static function withUserId(int $userId): self
    {
        return (new self("Seller profile not found for user ID: {$userId}"))
            ->withContext(['user_id' => $userId]);
    }
}
