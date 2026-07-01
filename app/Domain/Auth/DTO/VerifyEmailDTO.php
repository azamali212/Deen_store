<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class VerifyEmailDTO
{
    public function __construct(
        public string $token,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            token: self::cleanToken($data['token']),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    private static function cleanToken(
        mixed $token
    ): string {
        return trim((string) $token);
    }
}