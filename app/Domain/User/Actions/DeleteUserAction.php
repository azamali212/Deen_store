<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Services\UserService;

final readonly class DeleteUserAction
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(int $userId): void
    {
        $this->userService->deleteUser($userId);
    }
}
