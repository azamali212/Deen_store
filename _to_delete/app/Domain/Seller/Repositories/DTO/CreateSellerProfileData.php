<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\DTO;

final readonly class CreateSellerProfileData
{
    public function __construct(
        public int $userId,
        public string $storeName,
        public ?string $businessName,
        public ?string $businessType,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'store_name' => $this->storeName,
            'business_name' => $this->businessName,
            'business_type' => $this->businessType,
            // A store is never born approved — an admin has to review it
            // first, same gate every real seller on a marketplace goes
            // through before their listings go live.
            'status' => 'pending',
        ];
    }
}
