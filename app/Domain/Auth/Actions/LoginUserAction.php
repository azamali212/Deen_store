<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\AuthResult;
use App\Domain\Auth\DTO\LoginDTO;
use App\Domain\Auth\Services\AuthService;

final readonly class LoginUserAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        LoginDTO $dto
    ): AuthResult {
        return $this->authService->login($dto);
    }
}