<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Queries;

use App\Domain\Seller\Enums\SellerRenewalStatus;
use App\Models\SellerDocumentRenewal;
use Illuminate\Database\Eloquent\Builder;

final class SellerRenewalQuery
{
    public function byId(int $renewalId): Builder
    {
        return SellerDocumentRenewal::query()->whereKey($renewalId);
    }

    public function pendingForStoreAndType(int $sellerProfileId, string $documentType): Builder
    {
        return SellerDocumentRenewal::query()
            ->where('seller_profile_id', $sellerProfileId)
            ->where('document_type', $documentType)
            ->where('status', SellerRenewalStatus::PENDING->value);
    }

    public function forStore(int $sellerProfileId): Builder
    {
        return SellerDocumentRenewal::query()
            ->where('seller_profile_id', $sellerProfileId)
            ->latest('id');
    }

    /**
     * The admin queue. OLDEST first on purpose — a seller whose payouts are
     * frozen should not sit behind someone who uploaded this morning.
     */
    public function pendingQueue(): Builder
    {
        return SellerDocumentRenewal::query()
            ->where('status', SellerRenewalStatus::PENDING->value)
            ->with('sellerProfile:id,store_name,user_id,kyc_status,cnic_expires_at,licence_expires_at')
            ->oldest('id');
    }
}
