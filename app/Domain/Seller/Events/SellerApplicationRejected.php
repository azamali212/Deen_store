<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerApplicationRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerApplication $application,
    ) {}
}
