<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Services\PasswordHistoryService;
use App\Models\User;

final readonly class StorePasswordHistoryAction
{
    public function __construct(
        private PasswordHistoryService $service,
    ) {}

    public function execute(
        User $user,
        string $hashedPassword,
    ): void {
        $this->service->storePassword(
            user: $user,
            hashedPassword: $hashedPassword,
        );
    }
}
