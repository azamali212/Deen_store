<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Services\PasswordHistoryService;
use App\Models\User;

final readonly class EnsurePasswordNotReusedAction
{
    public function __construct(
        private PasswordHistoryService $service,
    ) {}

    public function execute(
        User $user,
        string $newPassword,
    ): void {
        $this->service->ensurePasswordHasNotBeenUsedBefore(
            user: $user,
            newPassword: $newPassword,
            historyLimit: config('auth_security.password_history.remember'),
        );
    }
}
