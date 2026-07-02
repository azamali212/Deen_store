<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\ForgotPasswordDTO;
use App\Domain\Auth\Services\PasswordResetService;

final readonly class ForgotPasswordAction
{
    public function __construct(
        private PasswordResetService $service,
    ) {}

    public function execute(
        ForgotPasswordDTO $dto,
    ): void {

        $this->service->request(
            $dto
        );
    }
}