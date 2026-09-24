<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 403 — segregation of duties, same rule as C12 (reviewing your own
// application): an admin must never act on their own store.
final class CannotManageOwnStoreException extends DomainException
{
    public static function forAdmin(int $adminId, int $sellerProfileId): self
    {
        return (new self('You cannot perform this action on your own store. Another admin must do it.'))
            ->withContext([
                'admin_id' => $adminId,
                'seller_profile_id' => $sellerProfileId,
            ]);
    }
}
