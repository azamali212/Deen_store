<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\CreateSellerApplicationDTO;
use App\Domain\Seller\Services\SellerApplicationService;
use App\Models\SellerApplication;
use App\Models\User;

final readonly class CreateSellerApplicationAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    public function execute(User $user, CreateSellerApplicationDTO $dto): SellerApplication
    {
        return $this->service->create($user, $dto);
    }
}
