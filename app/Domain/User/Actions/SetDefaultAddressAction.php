<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Events\DefaultAddressChanged;
use App\Domain\User\Services\AddressService;
use App\Models\UserAddress;

final readonly class SetDefaultAddressAction
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function execute(int $userId, int $addressId): UserAddress
    {
        $address = $this->addressService->setDefaultAddress($userId, $addressId);

        event(new DefaultAddressChanged($address));

        return $address;
    }
}
