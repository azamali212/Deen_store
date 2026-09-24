<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerApplication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerApplicationBlockedByAi
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $failures
     */
    public function __construct(
        public readonly SellerApplication $application,
        public readonly array $failures,
    ) {}
}
