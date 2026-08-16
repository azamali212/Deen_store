<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\TwoFactorProvider;

final readonly class EnableTwoFactorDTO
{
    public function __construct(
        public string $userId,
        public TwoFactorProvider $provider,
    ) {}
}