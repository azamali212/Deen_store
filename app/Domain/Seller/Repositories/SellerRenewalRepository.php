<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories;

use App\Domain\Seller\Repositories\Contracts\SellerRenewalRepositoryInterface;
use App\Domain\Seller\Repositories\Queries\SellerRenewalQuery;
use App\Models\SellerDocumentRenewal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class SellerRenewalRepository implements SellerRenewalRepositoryInterface
{
    public function __construct(
        private SellerRenewalQuery $renewals,
    ) {}

    public function findById(int $renewalId): ?SellerDocumentRenewal
    {
        return $this->renewals->byId($renewalId)->first();
    }

    public function lockPendingById(int $renewalId): ?SellerDocumentRenewal
    {
        return $this->renewals->byId($renewalId)->lockForUpdate()->first();
    }

    public function pendingForStoreAndType(int $sellerProfileId, string $documentType): ?SellerDocumentRenewal
    {
        return $this->renewals->pendingForStoreAndType($sellerProfileId, $documentType)->first();
    }

    public function listForStore(int $sellerProfileId): Collection
    {
        return $this->renewals->forStore($sellerProfileId)->get();
    }

    public function pendingQueue(int $perPage): LengthAwarePaginator
    {
        return $this->renewals->pendingQueue()->paginate($perPage);
    }

    public function create(array $attributes): SellerDocumentRenewal
    {
        return SellerDocumentRenewal::query()->create($attributes);
    }

    public function update(SellerDocumentRenewal $renewal, array $attributes): SellerDocumentRenewal
    {
        $renewal->update($attributes);

        return $renewal->refresh();
    }
}
