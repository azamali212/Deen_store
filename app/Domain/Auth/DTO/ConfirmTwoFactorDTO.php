<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class ConfirmTwoFactorDTO
{
    public function __construct(
        public string $userId,
        public string $code,
    ) {}
}