<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerAdminService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadBankProofAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(int $sellerProfileId): StreamedResponse
    {
        return $this->service->downloadBankProof($sellerProfileId);
    }
}
