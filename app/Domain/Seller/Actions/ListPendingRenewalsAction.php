<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerDocumentRenewalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListPendingRenewalsAction
{
    public function __construct(
        private SellerDocumentRenewalService $service,
    ) {}

    public function execute(int $perPage): LengthAwarePaginator
    {
        return $this->service->queue($perPage);
    }
}
