<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerAdminService;
use App\Models\SellerProfile;

final readonly class GetSellerDetailAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId): SellerProfile
    {
        return $this->service->get($sellerProfileId);
    }
}
