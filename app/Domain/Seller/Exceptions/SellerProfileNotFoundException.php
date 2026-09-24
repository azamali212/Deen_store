<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 404 — the user has no live business (never approved).
final class SellerProfileNotFoundException extends DomainException
{
    public static function forUser(int $userId): self
    {
        return (new self('You do not have an approved seller business yet.'))
            ->withContext(['user_id' => $userId]);
    }
}
