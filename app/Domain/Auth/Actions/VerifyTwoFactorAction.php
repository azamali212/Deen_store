<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\DTO\VerifyTwoFactorDTO;
use App\Domain\Auth\Services\TwoFactorService;

final readonly class VerifyTwoFactorAction
{
    public function __construct(
        private TwoFactorService $service,
    ) {}

    public function execute(
        VerifyTwoFactorDTO $dto,
    ): AuthResult {
        return $this->service
            ->verify(
                $dto,
            );
    }
}
