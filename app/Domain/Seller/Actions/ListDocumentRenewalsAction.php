<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerDocumentRenewalService;
use Illuminate\Support\Collection;

final readonly class ListDocumentRenewalsAction
{
    public function __construct(
        private SellerDocumentRenewalService $service,
    ) {}

    public function execute(int $userId): Collection
    {
        return $this->service->listForUser($userId);
    }
}
