<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\DisableTwoFactorDTO;
use App\Domain\Auth\Services\TwoFactorService;

final readonly class DisableTwoFactorAction
{
    public function __construct(
        private TwoFactorService $service,
    ) {}

    public function execute(
        DisableTwoFactorDTO $dto,
    ): void {

        $this->service
            ->disable(
                $dto,
            );
    }
}