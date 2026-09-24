<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerProfileUpdated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $changedFields  e.g. ['description', 'logo']
     */
    public function __construct(
        public readonly SellerProfile $profile,
        public readonly array $changedFields,
    ) {}
}
