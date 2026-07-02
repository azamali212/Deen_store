<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class ForgotPasswordDTO
{
    public function __construct(
        public string $email,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return new self(
            email: strtolower(trim((string) $data['email'])),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }
}