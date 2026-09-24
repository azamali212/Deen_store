<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 409
final class InvalidStoreNameChangeException extends DomainException
{
    public static function alreadyPending(): self
    {
        return new self('You already have a name change waiting for review. Withdraw it first if you want to ask for a different name.');
    }

    public static function nothingPending(int $sellerProfileId): self
    {
        return (new self('There is no name change waiting for review on this store.'))
            ->withContext(['seller_profile_id' => $sellerProfileId]);
    }

    public static function sameAsCurrent(): self
    {
        return new self('That is already your store name.');
    }
}
