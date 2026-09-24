<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Contracts;

use App\Models\SellerDocumentRenewal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SellerRenewalRepositoryInterface
{
    public function findById(int $renewalId): ?SellerDocumentRenewal;

    /** Locked for update — two admins clicking review at once (C13). */
    public function lockPendingById(int $renewalId): ?SellerDocumentRenewal;

    public function pendingForStoreAndType(int $sellerProfileId, string $documentType): ?SellerDocumentRenewal;

    public function listForStore(int $sellerProfileId): Collection;

    public function pendingQueue(int $perPage): LengthAwarePaginator;

    public function create(array $attributes): SellerDocumentRenewal;

    public function update(SellerDocumentRenewal $renewal, array $attributes): SellerDocumentRenewal;
}
