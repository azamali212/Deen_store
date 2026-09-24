<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\UpdateSellerApplicationDTO;
use App\Domain\Seller\Services\SellerApplicationService;
use App\Models\SellerApplication;

final readonly class UpdateSellerApplicationAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    public function execute(int $userId, UpdateSellerApplicationDTO $dto): SellerApplication
    {
        return $this->service->update($userId, $dto)->load('documents');
    }
}
