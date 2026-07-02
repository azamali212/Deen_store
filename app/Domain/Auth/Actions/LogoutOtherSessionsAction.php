<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\LogoutOtherSessionsDTO;
use App\Domain\Auth\Services\SessionService;

final readonly class LogoutOtherSessionsAction
{
    public function __construct(
        private SessionService $service,
    ) {}

    public function execute(
        LogoutOtherSessionsDTO $dto,
    ): void {

        $this->service->logoutOtherSessions(
            $dto
        );
    }
}