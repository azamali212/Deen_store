<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerDocumentRenewalService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadRenewalDocumentAction
{
    public function __construct(
        private SellerDocumentRenewalService $service,
    ) {}

    public function execute(int $renewalId, int $adminId): StreamedResponse
    {
        return $this->service->download($renewalId, $adminId);
    }
}
