<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;

final class SellerStoreNameChangeReviewed
{
    use Dispatchable;

    public function __construct(
        public readonly SellerProfile $profile,
        public readonly string $previousName,
        public readonly bool $approved,
        public readonly ?string $reason,
    ) {}
}
