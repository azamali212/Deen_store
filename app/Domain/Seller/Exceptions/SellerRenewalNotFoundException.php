<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 404
final class SellerRenewalNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return (new self('That document renewal was not found.'))
            ->withContext(['renewal_id' => $id]);
    }

    public static function fileMissing(): self
    {
        return new self('The uploaded file for this renewal is no longer on file.');
    }
}
