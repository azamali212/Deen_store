<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 403 — Q1: only customers with a verified email may apply.
final class EmailNotVerifiedForSellingException extends DomainException
{
    public static function forUser(int $userId): self
    {
        return (new self('Please verify your email address before applying to become a seller.'))
            ->withContext(['user_id' => $userId]);
    }
}
