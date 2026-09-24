<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Services\SellerDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadApplicationDocumentAction
{
    public function __construct(
        private SellerDocumentService $documents,
    ) {}

    public function execute(int $applicationId, SellerDocumentType $type): StreamedResponse
    {
        return $this->documents->download($applicationId, $type);
    }
}
