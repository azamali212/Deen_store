<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class RegisterCustomerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: self::cleanString($data['name']),
            email: self::cleanEmail($data['email']),
            password: (string) $data['password'],
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