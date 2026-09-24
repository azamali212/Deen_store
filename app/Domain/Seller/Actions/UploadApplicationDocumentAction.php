<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\UploadApplicationDocumentDTO;
use App\Domain\Seller\Events\SellerDocumentUploaded;
use App\Domain\Seller\Services\SellerApplicationService;
use App\Domain\Seller\Services\SellerDocumentService;
use App\Models\SellerApplicationDocument;

final readonly class UploadApplicationDocumentAction
{
    public function __construct(
        private SellerApplicationService $applications,
        private SellerDocumentService $documents,
    ) {}

    public function execute(int $userId, UploadApplicationDocumentDTO $dto): SellerApplicationDocument
    {
        // Resolved from the user id — a customer can only ever upload to
        // their OWN application (C5).
        $application = $this->applications->getForUser($userId);

        $document = $this->documents->upload($application, $dto);

        event(new SellerDocumentUploaded($document));

        return $document;
    }
}
