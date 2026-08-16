<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\ConfirmTwoFactorDTO;
use App\Domain\Auth\Services\TwoFactorService;

final readonly class ConfirmTwoFactorAction
{
    public function __construct(
        private TwoFactorService $service,
    ) {}

    public function execute(
        ConfirmTwoFactorDTO $dto,
    ): array {

        return $this->service->confirm(
            $dto,
        );
    }
}
