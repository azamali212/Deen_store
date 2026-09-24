<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class WithdrawStoreNameChangeAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId): SellerProfile
    {
        // No event: withdrawing your own pending request is not a state
        // change anyone else ever saw (C45 — the old name never moved).
        return $this->service->withdrawNameChange($userId);
    }
}
