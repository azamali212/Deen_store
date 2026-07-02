<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

use Carbon\CarbonInterface;

final readonly class CreatePasswordResetData
{
    public function __construct(
        public string $userId,
        public string $token,
        public CarbonInterface $expiresAt,
    ) {}

    public function toArray(): array
    {
        return [

            'user_id' => $this->userId,

            'token' => $this->token,

            'expires_at' => $this->expiresAt,

        ];
    }
}