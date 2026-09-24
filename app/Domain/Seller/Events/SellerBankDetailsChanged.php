<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * C7 — payout account added or changed. Carries only last-4 digits and
 * bank names: the full account number never travels through events,
 * queues, audit rows or emails.
 */
final class SellerBankDetailsChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerProfile $profile,
        public readonly bool $isFirstTime,
        public readonly ?string $oldLast4,
        public readonly string $newLast4,
        public readonly ?string $oldBankName,
        public readonly ?string $newBankName,
    ) {}
}
