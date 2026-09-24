<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\AiRiskLevel;
use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Services\SellerApplicationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListSellerApplicationsAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    // $status null = every submitted status (drafts are never listed).
    public function execute(?SellerApplicationStatus $status, int $perPage = 20, ?AiRiskLevel $risk = null): LengthAwarePaginator
    {
        return $this->service->listForAdmin($status, $perPage, $risk);
    }
}
