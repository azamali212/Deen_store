<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerStoreReactivated;
use App\Domain\Seller\Services\SellerAdminService;
use App\Models\SellerProfile;

final readonly class ReactivateSellerAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId, int $adminId): SellerProfile
    {
        $profile = $this->service->reactivate($sellerProfileId, $adminId);

        event(new SellerStoreReactivated($profile));

        return $profile->load('user:id,name,email');
    }
}
