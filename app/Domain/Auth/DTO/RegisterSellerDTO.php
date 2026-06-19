<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

final readonly class RegisterSellerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $storeName,
        public ?string $phone = null,
        public ?string $businessName = null,
        public ?string $businessType = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: self::cleanString($data['name']),
            email: self::cleanEmail($data['email']),
            password: (string) $data['password'],
            storeName: self::cleanString($data['store_name']),
            phone: self::nullableString($data, 'phone'),
            businessName: self::nullableString($data, 'business_name'),
            businessType: self::nullableString($data, 'business_type'),
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