<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Domain\Seller\Enums\SellerKycStatus;
use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired ONLY when the status actually moved (C29) — never once per day
 * while a condition holds.
 */
final class SellerKycStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly SellerProfile $profile,
        public readonly SellerKycStatus $previous,
        public readonly SellerKycStatus $current,
    ) {}
}
