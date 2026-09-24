<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerApplication;
use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Fired AFTER the approve transaction commits — so no email ever goes out
// for an approval that was rolled back.
final class SellerApplicationApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerApplication $application,
        public readonly SellerProfile $sellerProfile,
    ) {}
}
