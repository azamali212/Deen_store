<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerStoreNameChangeReviewed;
use App\Domain\Seller\Services\SellerAdminService;
use App\Models\SellerProfile;

final readonly class ReviewStoreNameChangeAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId, int $adminId, bool $approve, ?string $reason): SellerProfile
    {
        $result = $this->service->reviewNameChange($sellerProfileId, $adminId, $approve, $reason);

        event(new SellerStoreNameChangeReviewed(
            profile: $result['profile'],
            previousName: $result['previous_name'],
            approved: $result['approved'],
            reason: $result['reason'],
        ));

        return $result['profile'];
    }
}
