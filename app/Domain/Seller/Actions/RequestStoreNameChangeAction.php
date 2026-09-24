<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerStoreNameChangeRequested;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class RequestStoreNameChangeAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId, string $storeName): SellerProfile
    {
        $profile = $this->service->requestNameChange($userId, $storeName);

        event(new SellerStoreNameChangeRequested($profile));

        return $profile;
    }
}
