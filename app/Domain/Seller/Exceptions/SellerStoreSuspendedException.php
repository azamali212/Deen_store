<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 403 — P6-1: a suspended store can be VIEWED but not changed.
final class SellerStoreSuspendedException extends DomainException
{
    public static function forProfile(int $sellerProfileId): self
    {
        return (new self('Your store is currently suspended, so it cannot be changed. Please contact support.'))
            ->withContext(['seller_profile_id' => $sellerProfileId]);
    }
}
