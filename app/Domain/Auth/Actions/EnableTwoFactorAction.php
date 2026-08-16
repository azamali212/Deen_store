<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\EnableTwoFactorDTO;
use App\Domain\Auth\Services\TwoFactorService;

final readonly class EnableTwoFactorAction
{
    public function __construct(
        private TwoFactorService $service,
    ) {}

    public function execute(EnableTwoFactorDTO $dto): array
    {
        return $this->service->enable(
            $dto,
        );
    }
}