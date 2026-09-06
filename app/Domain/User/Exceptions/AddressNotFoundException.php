<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class AddressNotFoundException extends DomainException
{
    public static function withId(int $addressId): self
    {
        return (new self("Address not found with ID: {$addressId}"))
            ->withContext(['address_id' => $addressId]);
    }

    public static function forUser(int $addressId, int $userId): self
    {
        return (new self("Address ID {$addressId} not found for user ID: {$userId}"))
            ->withContext([
                'address_id' => $addressId,
                'user_id' => $userId,
            ]);
    }
}
