<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// P5-2 — the payout account does not match the verified bank statement.
final class SellerBankUnverified
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerProfile $profile,
        public readonly string $enteredLast4,
    ) {}
}
