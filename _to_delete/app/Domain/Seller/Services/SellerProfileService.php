<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\DTO\CreateSellerProfileDTO;
use App\Domain\Seller\Exceptions\DuplicateStoreNameException;
use App\Domain\Seller\Exceptions\SellerProfileAlreadyExistsException;
use App\Domain\Seller\Repositories\Contracts\SellerRepositoryInterface;
use App\Domain\Seller\Repositories\DTO\CreateSellerProfileData;
use App\Models\SellerProfile;

final readonly class SellerProfileService
{
    public function __construct(
        private SellerRepositoryInterface $repository,
    ) {}

    /**
     * @throws SellerProfileAlreadyExistsException when this user already has a store.
     * @throws DuplicateStoreNameException when the store name is already taken.
     */
    public function createProfile(CreateSellerProfileDTO $dto): SellerProfile
    {
        if ($this->repository->findByUserId($dto->userId) instanceof SellerProfile) {
            throw SellerProfileAlreadyExistsException::forUser($dto->userId);
        }

        if ($this->repository->storeNameExists($dto->storeName)) {
            throw DuplicateStoreNameException::withName($dto->storeName);
        }

        return $this->repository->create(
            new CreateSellerProfileData(
                userId: $dto->userId,
                storeName: $dto->storeName,
                businessName: $dto->businessName,
                businessType: $dto->businessType,
            ),
        );
    }
}
