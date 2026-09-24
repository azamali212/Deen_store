<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerStoreReopened;
use App\Domain\Seller\Services\SellerAdminService;
use App\Models\SellerProfile;

final readonly class ReopenSellerStoreAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId, int $adminId): SellerProfile
    {
        $profile = $this->service->reopen($sellerProfileId, $adminId);

        event(new SellerStoreReopened($profile));

        return $profile;
    }
}
