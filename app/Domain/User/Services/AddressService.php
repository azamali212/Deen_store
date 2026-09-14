<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\DTO\UserAddressDTO;
use App\Domain\User\Exceptions\AddressLimitExceededException;
use App\Domain\User\Exceptions\AddressNotFoundException;
use App\Domain\User\Repositories\Contracts\UserAddressRepositoryInterface;
use App\Models\UserAddress;
use Illuminate\Support\Collection;

final readonly class AddressService
{
    private const MAX_ADDRESSES_PER_USER = 10;

    public function __construct(
        private UserAddressRepositoryInterface $addresses,
    ) {}

    public function listAddresses(int $userId): Collection
    {
        return $this->addresses->listForUser($userId);
    }

    public function getAddress(int $userId, int $addressId): ?UserAddress
    {
        return $this->addresses->findByIdForUser($userId, $addressId);
    }

    public function addAddress(int $userId, UserAddressDTO $dto): UserAddress
    {
        $this->ensureUnderAddressLimit($userId);

        return $this->addresses->create($userId, $dto);
    }

    public function updateAddress(int $userId, int $addressId, UserAddressDTO $dto): UserAddress
    {
        return $this->addresses->update($userId, $addressId, $dto);
    }

    public function setDefaultAddress(int $userId, int $addressId): UserAddress
    {
        return $this->addresses->setDefault($userId, $addressId);
    }

    public function deleteAddress(int $userId, int $addressId): bool
    {
        $address = $this->addresses->findByIdForUser($userId, $addressId)
            ?? throw AddressNotFoundException::forUser($addressId, $userId);

        $wasDefault = $address->is_default;

        $deleted = $this->addresses->delete($userId, $addressId);

        if ($deleted && $wasDefault) {
            $this->promoteNextAddressToDefault($userId);
        }

        return $deleted;
    }

    private function ensureUnderAddressLimit(int $userId): void
    {
        if ($this->addresses->countForUser($userId) >= self::MAX_ADDRESSES_PER_USER) {
            throw AddressLimitExceededException::forUser($userId, self::MAX_ADDRESSES_PER_USER);
        }
    }

    private function promoteNextAddressToDefault(int $userId): void
    {
        $next = $this->addresses->listForUser($userId)->first();

        if ($next !== null) {
            $this->addresses->setDefault($userId, $next->id);
        }
    }
}
