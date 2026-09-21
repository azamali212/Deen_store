<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Events\UserActivated;
use App\Domain\User\Services\UserStatusService;
use App\Models\User;

final readonly class ActivateUserAction
{
    public function __construct(
        private UserStatusService $statusService,
    ) {}

    public function execute(int $userId): User
    {
        $user = $this->statusService->activate($userId);

        event(new UserActivated($user));

        return $user;
    }
}
