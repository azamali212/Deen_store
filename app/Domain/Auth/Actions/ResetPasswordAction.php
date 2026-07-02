<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\ResetPasswordDTO;
use App\Domain\Auth\Services\PasswordResetService;

final readonly class ResetPasswordAction
{
    public function __construct(
        private PasswordResetService $service,
    ) {}

    public function execute(
        ResetPasswordDTO $dto,
    ): void {

        $this->service->reset(
            $dto
        );
    }
}