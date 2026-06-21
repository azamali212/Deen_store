<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\RegisterAdminDTO;
use App\Domain\Auth\Services\AuthService;
use App\Models\User;

final readonly class RegisterAdminAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        RegisterAdminDTO $dto
    ): User {
        return $this->authService
            ->registerAdmin($dto);
    }
}