<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerProfileUpdated;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class DeleteSellerLogoAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId): SellerProfile
    {
        $hadLogo = $this->service->getForUser($userId)->logo_path !== null;

        $profile = $this->service->deleteLogo($userId);

        if ($hadLogo) {
            event(new SellerProfileUpdated($profile, ['logo']));
        }

        return $profile;
    }
}
