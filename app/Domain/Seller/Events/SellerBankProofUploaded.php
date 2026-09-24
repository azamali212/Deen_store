<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerBankProofUploaded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerProfile $profile,
        // true = the AI matched it, so no admin review is needed.
        public readonly bool $autoMatched,
    ) {}
}
