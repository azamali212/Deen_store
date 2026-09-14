<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class AddressLimitExceededException extends DomainException
{
    public static function forUser(int $userId, int $limit): self
    {
        return (new self("User ID {$userId} has reached the maximum of {$limit} saved addresses."))
            ->withContext(['user_id' => $userId, 'limit' => $limit]);
    }
}
