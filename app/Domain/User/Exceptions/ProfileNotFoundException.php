<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class ProfileNotFoundException extends DomainException
{
    public static function withUserId(int $userId): self
    {
        return (new self("Profile not found for user ID: {$userId}"))
            ->withContext(['user_id' => $userId]);
    }

    public static function withId(int $profileId): self
    {
        return (new self("Profile not found with ID: {$profileId}"))
            ->withContext(['profile_id' => $profileId]);
    }
}
