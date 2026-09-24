<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerStoreReopenRequested;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class RequestStoreReopenAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId): SellerProfile
    {
        $profile = $this->service->requestReopen($userId);

        event(new SellerStoreReopenRequested($profile));

        return $profile;
    }
}
