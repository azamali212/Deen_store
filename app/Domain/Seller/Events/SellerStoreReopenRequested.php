<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;

final class SellerStoreReopenRequested
{
    use Dispatchable;

    public function __construct(
        public readonly SellerProfile $profile,
    ) {}
}
