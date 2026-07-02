<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class ResetPasswordDTO
{
    public function __construct(
        public string $token,
        public string $password,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return new self(
            token: trim((string) $data['token']),
            password: (string) $data['password'],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}