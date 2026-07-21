<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class UnlockAccountDTO
{
    public function __construct(
        public string $userId,
        public string $unlockedByUserId,
        public string $reason,
    ) {}

    public static function fromArray(array $data, string $unlockedByUserId): self
    {
        return new self(
            userId: (string) $data['user_id'],
            unlockedByUserId: $unlockedByUserId,
            reason: trim((string) $data['reason']),
        );
    }
}