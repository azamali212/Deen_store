<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\SuspendSellerDTO;
use App\Domain\Seller\Events\SellerStoreSuspended;
use App\Domain\Seller\Services\SellerAdminService;
use App\Models\SellerProfile;

final readonly class SuspendSellerAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId, SuspendSellerDTO $dto): SellerProfile
    {
        $profile = $this->service->suspend($sellerProfileId, $dto);

        // After the transaction commits (C15) — no email for a rolled-back
        // suspension.
        event(new SellerStoreSuspended($profile, $dto->reason));

        return $profile->load(['user:id,name,email', 'suspendedBy:id,name,email']);
    }
}
