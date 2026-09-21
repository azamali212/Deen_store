<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use App\Models\UserAddress;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AddressUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly UserAddress $address,
    ) {}
}
