<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\AddressService;
use Illuminate\Support\Collection;

final readonly class ListAddressesAction
{
    public function __construct(
        private AddressService $addressService,
    ) {}

    public function execute(int $userId): Collection
    {
        return $this->addressService->listAddresses($userId);
    }
}
