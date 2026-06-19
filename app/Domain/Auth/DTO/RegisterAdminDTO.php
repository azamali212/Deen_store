<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Permissions\Enums\SystemRole;

final readonly class RegisterAdminDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public SystemRole $role,
        public string $createdByUserId,
        public ?string $phone = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data, string $createdByUserId): self
    {
        return new self(
            name: self::cleanString($data['name']),
            email: self::cleanEmail($data['email']),
            password: (string) $data['password'],
            role: SystemRole::from((string) $data['role']),
            createdByUserId: $createdByUserId,
            phone: self::nullableString($data, 'phone'),
            ipAddress: self::nullableString($data, 'ip_address'),
            userAgent: self::nullableString($data, 'user_agent'),
        );
    }

    private static function cleanEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    private static function cleanString(mixed $value): string
    {
        return trim((string) $value);
    }

    private static function nullableString(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== ''
            ? trim((string) $data[$key])
            : null;
    }
}