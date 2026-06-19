<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class LogoutDTO
{
    public function __construct(
        public string $userId,
        public ?string $tokenId = null,
        public bool $logoutAllDevices = false,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data, string $userId): self
    {
        return new self(
            userId: $userId,
            tokenId: self::nullableString($data, 'token_id'),
            logoutAllDevices: (bool) ($data['logout_all_devices'] ?? false),
            ipAddress: self::nullableString($data, 'ip_address'),
            userAgent: self::nullableString($data, 'user_agent'),
        );
    }

    private static function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== ''
            ? trim((string) $data[$key])
            : null;
    }
}