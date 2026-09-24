<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// First submit: draft -> pending.
final class SellerApplicationSubmitted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerApplication $application,
    ) {}
}
