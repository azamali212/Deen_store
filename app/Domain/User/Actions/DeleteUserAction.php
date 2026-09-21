<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Events\UserAccountDeleted;
use App\Domain\User\Services\UserService;
use App\Models\User;

final readonly class DeleteUserAction
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(int $userId): User
    {
        $user = $this->userService->deleteUser($userId);

        event(new UserAccountDeleted($user));

        return $user;
    }
}
