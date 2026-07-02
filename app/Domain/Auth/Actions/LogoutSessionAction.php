<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\LogoutSessionDTO;
use App\Domain\Auth\Services\SessionService;

final readonly class LogoutSessionAction
{
    public function __construct(
        private SessionService $service,
    ) {}

    public function execute(
        LogoutSessionDTO $dto,
    ): void {

        $this->service
            ->logoutSession(
                $dto
            );
    }
}