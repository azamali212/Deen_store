<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\Events\UserSuspended;
use App\Domain\User\Services\UserStatusService;
use App\Models\User;

final readonly class SuspendUserAction
{
    public function __construct(
        private UserStatusService $statusService,
    ) {}

    public function execute(int $userId, ?string $reason = null): User
    {
        $user = $this->statusService->suspend($userId, $reason);

        event(new UserSuspended($user, $reason));

        return $user;
    }
}
