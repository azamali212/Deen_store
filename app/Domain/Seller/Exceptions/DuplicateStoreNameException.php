<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 422 — rendered in the same shape as a validation error on `store_name`.
final class DuplicateStoreNameException extends DomainException
{
    public static function taken(string $storeName): self
    {
        return (new self("The store name '{$storeName}' is already taken."))
            ->withContext(['store_name' => $storeName]);
    }
}
