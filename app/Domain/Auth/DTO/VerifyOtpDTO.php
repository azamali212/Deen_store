<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\OtpPurpose;

final readonly class VerifyOtpDTO
{
    public function __construct(
        public string $identifier,
        public string $code,
        public OtpPurpose $purpose,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            identifier: self::cleanIdentifier($data['identifier']),
            code: self::cleanString($data['code']),
            purpose: OtpPurpose::from((string) $data['purpose']),
            ipAddress: self::nullableString($data, 'ip_address'),
            userAgent: self::nullableString($data, 'user_agent'),
        );
    }

    private static function cleanIdentifier(mixed $identifier): string
    {
        return strtolower(trim((string) $identifier));
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