<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 422 — C34: closing a business is not a one-click action. The seller
// types their own store name back to prove they meant it.
final class StoreClosureNotConfirmedException extends DomainException
{
    public static function nameDoesNotMatch(): self
    {
        return new self('The store name you typed does not match. Type it exactly as it appears on your store to confirm.');
    }
}
