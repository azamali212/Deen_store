<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\RegisterSellerDTO;
use App\Domain\Auth\Services\AuthService;
use App\Models\User;

final readonly class RegisterSellerAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        RegisterSellerDTO $dto
    ): User {
        return $this->authService
            ->registerSeller($dto);
    }
}