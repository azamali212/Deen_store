<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Services\SellerAdminService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListSellersAction
{
    public function __construct(
        private SellerAdminService $service,
    ) {}

    public function execute(
        ?SellerProfileStatus $status,
        ?BankVerificationStatus $bank,
        int $perPage = 20,
        bool $namePendingOnly = false,
    ): LengthAwarePaginator {
        return $this->service->list($status, $bank, $perPage, $namePendingOnly);
    }
}
