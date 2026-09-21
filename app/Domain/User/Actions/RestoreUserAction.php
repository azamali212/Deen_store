<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Events\UserAccountRestored;
use App\Domain\User\Services\UserService;
use App\Models\User;

final readonly class RestoreUserAction
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(int $userId): User
    {
        $user = $this->userService->restoreUser($userId);

        event(new UserAccountRestored($user));

        return $user;
    }
}
