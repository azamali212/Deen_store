<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories;

use App\Domain\Seller\DTO\CreateSellerApplicationDTO;
use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Repositories\Contracts\SellerApplicationRepositoryInterface;
use App\Domain\Seller\Repositories\Queries\SellerApplicationQuery;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class SellerApplicationRepository implements SellerApplicationRepositoryInterface
{
    public function __construct(
        private SellerApplicationQuery $applications,
    ) {}

    public function findForUser(int $userId): ?SellerApplication
    {
        return $this->applications->forUser($userId)->first();
    }

    public function findById(int $applicationId): ?SellerApplication
    {
        return $this->applications->byId($applicationId)->first();
    }

    public function storeNameExists(string $storeName, ?int $exceptApplicationId = null): bool
    {
        return $this->applications->byStoreName($storeName, $exceptApplicationId)->exists();
    }

    public function create(int $userId, CreateSellerApplicationDTO $dto): SellerApplication
    {
        return SellerApplication::query()->create([
            'user_id' => $userId,
            'store_name' => $dto->storeName,
            'business_name' => $dto->businessName,
            'business_type' => $dto->businessType->value,
            // Always set in code, never a DB default (C9).
            'status' => SellerApplicationStatus::DRAFT->value,
            // The request only lets this through when the box was ticked.
            'document_processing_consent_at' => $dto->acceptedDocumentProcessing ? now() : null,
        ]);
    }

    public function update(SellerApplication $application, array $attributes): SellerApplication
    {
        $application->update($attributes);

        return $application->refresh();
    }

    public function findDocument(SellerApplication $application, SellerDocumentType $type): ?SellerApplicationDocument
    {
        return $application->documents()
            ->where('document_type', $type->value)
            ->first();
    }

    public function saveDocument(SellerApplication $application, SellerDocumentType $type, array $attributes): SellerApplicationDocument
    {
        // One row per (application, type) — the UNIQUE index guarantees it,
        // updateOrCreate makes a re-upload replace instead of duplicate.
        return SellerApplicationDocument::query()->updateOrCreate(
            [
                'seller_application_id' => $application->id,
                'document_type' => $type->value,
            ],
            $attributes,
        );
    }

    public function uploadedDocumentTypes(SellerApplication $application): array
    {
        // toBase() skips the enum cast, so these are plain strings that can
        // be array_diff'ed against SellerDocumentType::required().
        return $application->documents()
            ->toBase()
            ->pluck('document_type')
            ->all();
    }

    public function paginateForAdmin(?SellerApplicationStatus $status, int $perPage, ?AiRiskLevel $risk = null): LengthAwarePaginator
    {
        return $this->applications->forAdmin($status, $risk)->paginate($perPage);
    }

    public function lockForReview(int $applicationId): ?SellerApplication
    {
        return $this->applications->byId($applicationId)->lockForUpdate()->first();
    }
}
