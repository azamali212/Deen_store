<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class LogoutOtherSessionsDTO
{
    public function __construct(
        public string $userId,
        public string $currentTokenId,
    ) {}

    public static function fromUser(
        string $userId,
        string $currentTokenId,
    ): self {

        return new self(

            userId: $userId,

            currentTokenId: $currentTokenId,

        );
    }
}