<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerDocumentRenewal;
use Illuminate\Foundation\Events\Dispatchable;

final class SellerDocumentRenewalReviewed
{
    use Dispatchable;

    public function __construct(
        public readonly SellerDocumentRenewal $renewal,
        public readonly bool $approved,
    ) {}
}
