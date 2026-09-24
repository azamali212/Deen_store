<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 403 — P8-5: the seller closed this store themselves. Different from a
// suspension, and the message says so, because the way out is different:
// a suspension is appealed, a closure is reopened on request.
final class SellerStoreClosedException extends DomainException
{
    public static function forProfile(int $sellerProfileId): self
    {
        return (new self('Your store is closed. Request to reopen it before making any changes.'))
            ->withContext(['seller_profile_id' => $sellerProfileId]);
    }
}
