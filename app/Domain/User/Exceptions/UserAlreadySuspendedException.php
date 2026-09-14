<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class UserAlreadySuspendedException extends DomainException
{
    public static function forUser(int|string $userId): self
    {
        return (new self("User ID {$userId} is already suspended."))
            ->withContext(['user_id' => $userId]);
    }
}
