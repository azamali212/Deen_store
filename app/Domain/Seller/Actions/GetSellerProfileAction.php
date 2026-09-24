<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class GetSellerProfileAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId): SellerProfile
    {
        return $this->service->getForUser($userId);
    }
}
