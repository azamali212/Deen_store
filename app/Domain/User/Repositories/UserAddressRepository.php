<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\User\DTO\UserAddressDTO;
use App\Domain\User\Exceptions\AddressNotFoundException;
use App\Domain\User\Repositories\Contracts\UserAddressRepositoryInterface;
use App\Domain\User\Repositories\Queries\UserAddressQuery;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class UserAddressRepository implements UserAddressRepositoryInterface
{
    public function __construct(
        private UserAddressQuery $addresses,
    ) {}

    public function findById(int $addressId): ?UserAddress
    {
        return $this->addresses->byId($addressId)->first();
    }

    public function findByIdForUser(int $userId, int $addressId): ?UserAddress
    {
        return $this->addresses->byIdForUser($userId, $addressId)->first();
    }

    public function listForUser(int $userId): Collection
    {
        return $this->addresses->forUser($userId)->get();
    }

    public function findDefaultForUser(int $userId): ?UserAddress
    {
        return $this->addresses->defaultForUser($userId)->first();
    }

    public function countForUser(int $userId): int
    {
        return $this->addresses->forUser($userId)->count();
    }

    public function create(int $userId, UserAddressDTO $dto): UserAddress
    {
        return DB::transaction(function () use ($userId, $dto): UserAddress {
            if ($dto->isDefault) {
                $this->clearDefaultForOthers($userId);
            }

            return UserAddress::query()->create(
                $this->attributes($userId, $dto),
            );
        });
    }

    public function update(int $userId, int $addressId, UserAddressDTO $dto): UserAddress
    {
        return DB::transaction(function () use ($userId, $addressId, $dto): UserAddress {
            $address = $this->findOrFail($userId, $addressId);

            if ($dto->isDefault) {
                $this->clearDefaultForOthers($userId, except: $addressId);
            }

            $address->update(
                $this->attributes($userId, $dto, withUserId: false),
            );

            return $address->refresh();
        });
    }

    public function setDefault(int $userId, int $addressId): UserAddress
    {
        return DB::transaction(function () use ($userId, $addressId): UserAddress {
            $address = $this->findOrFail($userId, $addressId);

            $this->clearDefaultForOthers($userId, except: $addressId);

            $address->update(['is_default' => true]);

            return $address->refresh();
        });
    }

    public function delete(int $userId, int $addressId): bool
    {
        return $this->addresses->byIdForUser($userId, $addressId)->delete() > 0;
    }

    private function findOrFail(int $userId, int $addressId): UserAddress
    {
        return $this->findByIdForUser($userId, $addressId)
            ?? throw AddressNotFoundException::forUser($addressId, $userId);
    }

    /**
     * Unset `is_default` on every other address belonging to this user, so
     * exactly one address ever stays flagged as default. Every caller runs
     * this inside DB::transaction(), so the "clear old default, then set
     * the new one" pair can never be observed half-done by a concurrent
     * request — the row lock is held until the transaction commits.
     */
    private function clearDefaultForOthers(int $userId, ?int $except = null): void
    {
        UserAddress::query()
            ->where('user_id', $userId)
            ->when(
                $except !== null,
                fn (Builder $query): Builder => $query->whereKeyNot($except),
            )
            ->update(['is_default' => false]);
    }

    /**
     * Map the DTO onto database column names in exactly one place, so
     * create() and update() can never drift apart when a new address field
     * is added later.
     */
    private function attributes(int $userId, UserAddressDTO $dto, bool $withUserId = true): array
    {
        $attributes = [
            'type' => $dto->type->value,
            'is_default' => $dto->isDefault,
            'label' => $dto->label,
            'recipient_name' => $dto->recipientName,
            'phone' => (string) $dto->phone,
            'address_line_1' => $dto->addressLine1,
            'address_line_2' => $dto->addressLine2,
            'city' => $dto->city,
            'state' => $dto->state,
            'postal_code' => $dto->postalCode?->value(),
            'country_code' => $dto->countryCode,
            'latitude' => $dto->latitude,
            'longitude' => $dto->longitude,
        ];

        return $withUserId ? ['user_id' => $userId] + $attributes : $attributes;
    }
}
