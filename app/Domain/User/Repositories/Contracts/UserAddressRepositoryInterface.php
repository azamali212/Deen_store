<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories\Contracts;

use App\Domain\User\DTO\UserAddressDTO;
use App\Models\UserAddress;
use Illuminate\Support\Collection;

interface UserAddressRepositoryInterface
{
    public function findById(int $addressId): ?UserAddress;

    public function findByIdForUser(int $userId, int $addressId): ?UserAddress;

    public function listForUser(int $userId): Collection;

    public function findDefaultForUser(int $userId): ?UserAddress;

    public function countForUser(int $userId): int;

    public function create(int $userId, UserAddressDTO $dto): UserAddress;

    public function update(int $userId, int $addressId, UserAddressDTO $dto): UserAddress;

    public function setDefault(int $userId, int $addressId): UserAddress;

    public function delete(int $userId, int $addressId): bool;
}
