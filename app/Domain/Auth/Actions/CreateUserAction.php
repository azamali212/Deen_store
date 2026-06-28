<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\CreateUserDTO;
use App\Domain\Auth\Services\AuthService;
use App\Models\User;

final readonly class CreateUserAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        CreateUserDTO $dto
    ): User {

        return $this->authService
            ->createUser(
                $dto
            );
    }
}