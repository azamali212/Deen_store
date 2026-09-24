<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerApplicationService;
use App\Models\SellerApplication;

final readonly class GetMySellerApplicationAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    public function execute(int $userId): SellerApplication
    {
        return $this->service->getForUser($userId)->load('documents');
    }
}
