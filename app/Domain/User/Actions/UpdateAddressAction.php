<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\UserAddressDTO;
use App\Domain\User\Services\AddressService;
use App\Models\UserAddress;

final readonly class UpdateAddressAction
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function execute(int $userId, int $addressId, UserAddressDTO $dto): UserAddress
    {
        return $this->addressService->updateAddress($userId, $addressId, $dto);
    }
}
