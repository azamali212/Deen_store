<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Exceptions\DomainException;

// 409 — suspending a suspended store, reactivating an active one.
final class InvalidSellerStatusTransitionException extends DomainException
{
    public static function cannot(string $action, SellerProfileStatus $current): self
    {
        return (new self("This store is already {$current->label()}, so it cannot be {$action}."))
            ->withContext([
                'action' => $action,
                'current_status' => $current->value,
            ]);
    }

    public static function reopenAlreadyRequested(): self
    {
        return new self('You have already asked for this store to be reopened. Our team will review it.');
    }
}
