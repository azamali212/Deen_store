<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\LogoutDTO;
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
    }
}