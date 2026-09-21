<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Contracts;

use App\Domain\Seller\Repositories\DTO\CreateSellerProfileData;
use App\Models\SellerProfile;

interface SellerRepositoryInterface
{
    public function findByUserId(int $userId): ?SellerProfile;

    public function storeNameExists(string $storeName): bool;

    public function create(CreateSellerProfileData $data): SellerProfile;
}
