<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Contracts;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Models\SellerApplication;
use App\Models\SellerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SellerProfileRepositoryInterface
{
    public function findForUser(int $userId): ?SellerProfile;

    public function createFromApplication(SellerApplication $application): SellerProfile;

    /** C44 — checked when a rename is REQUESTED and again when approved. */
    public function storeNameExists(string $storeName, ?int $exceptProfileId = null): bool;

    public function update(SellerProfile $profile, array $attributes): SellerProfile;

    public function findById(int $sellerProfileId): ?SellerProfile;

    public function paginateForAdmin(
        ?SellerProfileStatus $status,
        ?BankVerificationStatus $bank,
        int $perPage,
        bool $namePendingOnly = false,
    ): LengthAwarePaginator;

    /**
     * Re-reads the row with a FOR UPDATE lock — used inside suspend /
     * reactivate / bank-review transactions (same idea as C13).
     */
    public function lockById(int $sellerProfileId): ?SellerProfile;
}
