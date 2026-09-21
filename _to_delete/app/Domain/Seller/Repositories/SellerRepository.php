<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories;

use App\Domain\Seller\Exceptions\DuplicateStoreNameException;
use App\Domain\Seller\Repositories\Contracts\SellerRepositoryInterface;
use App\Domain\Seller\Repositories\DTO\CreateSellerProfileData;
use App\Models\SellerProfile;
use Illuminate\Database\UniqueConstraintViolationException;

final class SellerRepository implements SellerRepositoryInterface
{
    public function findByUserId(int $userId): ?SellerProfile
    {
        return SellerProfile::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function storeNameExists(string $storeName): bool
    {
        return SellerProfile::query()
            ->where('store_name', $storeName)
            ->exists();
    }

    /**
     * Two sellers racing to register the same store name both pass an
     * application-level uniqueness check, but only one can win at the
     * database level (store_name carries a unique index). We let the DB be
     * the final word and turn its rejection into a clean domain exception
     * instead of a raw 500.
     *
     * @throws DuplicateStoreNameException
     */
    public function create(CreateSellerProfileData $data): SellerProfile
    {
        try {
            return SellerProfile::query()->create(
                $data->toArray(),
            );
        } catch (UniqueConstraintViolationException) {
            throw DuplicateStoreNameException::withName($data->storeName);
        }
    }
}
