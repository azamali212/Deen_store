<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\AddressService;

final readonly class DeleteAddressAction
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function execute(int $userId, int $addressId): bool
    {
        return $this->addressService->deleteAddress($userId, $addressId);
    }
}
