<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\UserAddressDTO;
use App\Domain\User\Events\AddressAdded;
use App\Domain\User\Services\AddressService;
use App\Models\UserAddress;

final readonly class AddAddressAction
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function execute(int $userId, UserAddressDTO $dto): UserAddress
    {
        $address = $this->addressService->addAddress($userId, $dto);

        event(new AddressAdded($address));

        return $address;
    }
}
