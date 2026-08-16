<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

final readonly class CreateRecoveryCodeData
{
    public function __construct(
        public string $uuid,
        public string $userId,
        public string $code,
    ) {}

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'user_id' => $this->userId,
            'code' => $this->code,
        ];
    }
}