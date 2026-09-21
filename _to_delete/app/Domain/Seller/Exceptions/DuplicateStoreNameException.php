<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

final class DuplicateStoreNameException extends DomainException
{
    public static function withName(string $storeName): self
    {
        return (new self("A store already exists with the name: {$storeName}"))
            ->withContext(['store_name' => $storeName]);
    }
}
