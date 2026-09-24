<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerBankProofReviewed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerProfile $profile,
        public readonly bool $verified,
        public readonly ?string $reason,
    ) {}
}
