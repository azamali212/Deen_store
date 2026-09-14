<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\UpdateUserDTO;
use App\Domain\User\Services\UserService;
use App\Models\User;

final readonly class UpdateUserAction
{
    public function __construct(
        private UserService $userService,
    ) {}

    public function execute(int $userId, UpdateUserDTO $dto): User
    {
        return $this->userService->updateUser($userId, $dto);
    }
}
