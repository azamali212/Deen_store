<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 404
final class SellerApplicationNotFoundException extends DomainException
{
    public static function forUser(int $userId): self
    {
        return (new self('You have not started a seller application yet.'))
            ->withContext(['user_id' => $userId]);
    }

    public static function withId(int $applicationId): self
    {
        return (new self("Seller application #{$applicationId} was not found."))
            ->withContext(['application_id' => $applicationId]);
    }
}
