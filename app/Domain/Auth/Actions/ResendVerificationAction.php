<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\ResendVerificationDTO;
use App\Domain\Auth\Services\EmailVerificationService;

final readonly class ResendVerificationAction
{
    public function __construct(
        private EmailVerificationService $service,
    ) {}

    public function execute(
        ResendVerificationDTO $dto,
    ): void {

        $this->service->resend(
            $dto
        );
    }
}