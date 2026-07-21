<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\UnlockAccountDTO;
use App\Domain\Auth\Services\AccountLockoutService;
use App\Models\User;

final readonly class UnlockAccountAction
{
    public function __construct(
        private AccountLockoutService $service,
    ) {}

    public function execute(UnlockAccountDTO $dto): User
    {
        return $this->service->unlockByAdmin($dto);
    }
}