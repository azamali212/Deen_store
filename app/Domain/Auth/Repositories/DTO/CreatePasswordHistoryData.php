<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

final readonly class CreatePasswordHistoryData
{
    public function __construct(
        public string $userId,
        public string $password,
    ) {}
}
