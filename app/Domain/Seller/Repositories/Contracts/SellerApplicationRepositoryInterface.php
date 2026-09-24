<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Contracts;

use App\Domain\Seller\DTO\CreateSellerApplicationDTO;
use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SellerApplicationRepositoryInterface
{
    public function findForUser(int $userId): ?SellerApplication;

    public function findById(int $applicationId): ?SellerApplication;

    public function storeNameExists(string $storeName, ?int $exceptApplicationId = null): bool;

    public function create(int $userId, CreateSellerApplicationDTO $dto): SellerApplication;

    public function update(SellerApplication $application, array $attributes): SellerApplication;

    public function findDocument(SellerApplication $application, SellerDocumentType $type): ?SellerApplicationDocument;

    public function saveDocument(SellerApplication $application, SellerDocumentType $type, array $attributes): SellerApplicationDocument;

    /**
     * @return array<int, string> raw document_type values already uploaded
     */
    public function uploadedDocumentTypes(SellerApplication $application): array;

    public function paginateForAdmin(?SellerApplicationStatus $status, int $perPage, ?AiRiskLevel $risk = null): LengthAwarePaginator;

    /**
     * Re-reads the row with a FOR UPDATE lock — used inside the review
     * transaction so two admins can't approve/reject at the same moment.
     */
    public function lockForReview(int $applicationId): ?SellerApplication;
}
