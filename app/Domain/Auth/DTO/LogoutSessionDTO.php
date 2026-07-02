<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class LogoutSessionDTO
{
    public function __construct(
        public string $userId,
        public string $tokenId,
    ) {}

    public static function fromArray(
        array $data,
        string $userId,
    ): self {

        return new self(

            userId: $userId,

            tokenId: (string) $data['token_id'],

        );
    }
}