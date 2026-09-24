<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Domain\Seller\Repositories\Queries\SellerProfileQuery;
use App\Models\SellerApplication;
use App\Models\SellerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class SellerProfileRepository implements SellerProfileRepositoryInterface
{
    public function __construct(
        private SellerProfileQuery $profiles,
    ) {}

    public function findForUser(int $userId): ?SellerProfile
    {
        return $this->profiles->forUser($userId)->first();
    }

    /**
     * The live business, born from the approved application. Identity
     * fields are COPIED from what the admin just verified and are locked
     * from here on (C6); logo/description/address/bank start empty and
     * are filled in by the seller afterwards (Phase 4).
     */
    public function createFromApplication(SellerApplication $application): SellerProfile
    {
        return SellerProfile::query()->create([
            'user_id' => $application->user_id,
            'seller_application_id' => $application->id,
            'store_name' => $application->store_name,
            'business_name' => $application->business_name,
            'business_type' => $application->business_type->value,
            'status' => SellerProfileStatus::ACTIVE->value,
            // C9 — always set in code. The expiry DATES are copied in
            // straight after, by SellerApplicationService::approve() (P8-1).
            'kyc_status' => SellerKycStatus::VALID->value,
        ]);
    }

    public function storeNameExists(string $storeName, ?int $exceptProfileId = null): bool
    {
        return $this->profiles->byStoreName($storeName, $exceptProfileId)->exists();
    }

    public function update(SellerProfile $profile, array $attributes): SellerProfile
    {
        $profile->update($attributes);

        return $profile->refresh();
    }

    public function findById(int $sellerProfileId): ?SellerProfile
    {
        return $this->profiles->byId($sellerProfileId)->first();
    }

    public function paginateForAdmin(
        ?SellerProfileStatus $status,
        ?BankVerificationStatus $bank,
        int $perPage,
        bool $namePendingOnly = false,
    ): LengthAwarePaginator {
        return $this->profiles->forAdmin($status, $bank, $namePendingOnly)->paginate($perPage);
    }

    public function lockById(int $sellerProfileId): ?SellerProfile
    {
        return $this->profiles->byId($sellerProfileId)->lockForUpdate()->first();
    }
}
