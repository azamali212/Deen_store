<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\RegisterCustomerDTO;
use App\Domain\Auth\Services\AuthService;
use App\Models\User;

final readonly class RegisterCustomerAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        RegisterCustomerDTO $dto
    ): User {
        return $this->authService
            ->registerCustomer($dto);
    }
}