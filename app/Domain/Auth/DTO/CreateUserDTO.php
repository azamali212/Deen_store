<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Permissions\Enums\SystemRole;

final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone,
        public SystemRole $role,
        public string $createdByUserId,
        public ?string $ipAddress,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data, string $createdByUserId, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return new self(
            name: self::cleanString(
                $data['name'],
            ),
            email: self::cleanEmail(
                $data['email'],
            ),
            password: (string) $data['password'],
            phone: self::nullableString(
                $data,
                'phone',
            ),
            role: SystemRole::from(
                (string) $data['role'],
            ),
            createdByUserId: $createdByUserId,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    /**
     * Public self-registration factory — used when a visitor signs
     * themselves up (no authenticated admin exists to pick a role or an
     * ID to blame). $role is a fixed value the CALLER controls in code
     * (e.g. SystemRole::CUSTOMER), never read from request input — that
     * is what stops a self-registering visitor from granting themselves
     * an elevated role.
     */
    public static function forSelfRegistration(
        array $data,
        SystemRole $role,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            name: self::cleanString(
                $data['name'],
            ),
            email: self::cleanEmail(
                $data['email'],
            ),
            password: (string) $data['password'],
            phone: self::nullableString(
                $data,
                'phone',
            ),
            role: $role,
            createdByUserId: 'self',
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    private static function cleanEmail(
        mixed $email,
    ): string {
        return strtolower(
            trim((string) $email),
        );
    }

    private static function cleanString(
        mixed $value,
    ): string {
        return trim(
            (string) $value,
        );
    }

    private static function nullableString(
        array $data,
        string $key,
    ): ?string {
        return isset($data[$key]) && $data[$key] !== ''
            ? trim((string) $data[$key])
            : null;
    }
}
