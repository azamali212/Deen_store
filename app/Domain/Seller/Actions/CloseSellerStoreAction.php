<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerStoreClosed;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class CloseSellerStoreAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId, string $confirmStoreName, ?string $reason): SellerProfile
    {
        $profile = $this->service->close($userId, $confirmStoreName, $reason);

        // C15 — after the transaction commits, never inside it.
        event(new SellerStoreClosed($profile));

        return $profile;
    }
}
