<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Exceptions\DomainException;

// 409 — the action isn't allowed in the application's current status
// (e.g. uploading a document while it's pending, reviewing it twice).
final class InvalidApplicationStatusTransitionException extends DomainException
{
    public static function cannot(string $action, SellerApplicationStatus $current): self
    {
        return (new self("You cannot {$action} an application that is {$current->label()}."))
            ->withContext([
                'action' => $action,
                'current_status' => $current->value,
            ]);
    }
}
