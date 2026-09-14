<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class PreferenceNotFoundException extends DomainException
{
    public static function withUserId(int $userId): self
    {
        return (new self("Preferences not found for user ID: {$userId}"))
            ->withContext(['user_id' => $userId]);
    }
}
