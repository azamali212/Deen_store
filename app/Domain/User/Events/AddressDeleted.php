<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class AddressDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly int $addressId,
    ) {}
}
