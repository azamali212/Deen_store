<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 404 — admin asked for a store id that does not exist.
final class SellerProfileNotFoundByAdminException extends DomainException
{
    public static function withId(int $sellerProfileId): self
    {
        return (new self("Seller store #{$sellerProfileId} was not found."))
            ->withContext(['seller_profile_id' => $sellerProfileId]);
    }
}
