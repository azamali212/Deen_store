<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\CreateSellerProfileDTO;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class CreateSellerProfileAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(CreateSellerProfileDTO $dto): SellerProfile
    {
        return $this->service->createProfile($dto);
    }
}
