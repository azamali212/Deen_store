<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\ReviewBankProofDTO;
use App\Domain\Seller\Events\SellerBankProofReviewed;
use App\Domain\Seller\Services\SellerAdminService;
use App\Models\SellerProfile;

final readonly class ReviewBankProofAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId, ReviewBankProofDTO $dto): SellerProfile
    {
        $profile = $this->service->reviewBankProof($sellerProfileId, $dto);

        event(new SellerBankProofReviewed($profile, $dto->isVerification(), $dto->reason));

        return $profile->load(['user:id,name,email', 'bankVerifiedBy:id,name,email']);
    }
}
