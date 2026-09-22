<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\LogoutDTO;
use App\Domain\Auth\Events\Data\LogoutEventData;
use App\Domain\Auth\Events\UserLoggedOut;
use App\Domain\Auth\Services\AuthService;
use App\Models\User;

final readonly class LogoutUserAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        User $user,
        LogoutDTO $dto
    ): void {
        $this->authService->logout(
            $user,
            $dto
        );

        event(new UserLoggedOut(
            new LogoutEventData(
                userId: $dto->userId,
                email: $user->email,
                panel: $dto->panel,
                sessionId: $dto->tokenId,
                logoutAllDevices: $dto->logoutAllDevices,
                ipAddress: $dto->ipAddress,
                userAgent: $dto->userAgent,
                occurredAt: now(),
            ),
        ));
    }
}
