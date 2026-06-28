<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Enums\LoginProvider;

final readonly class VerifyOtpDTO
{
    public function __construct(
        public string $identifier,
        public string $code,
        public OtpPurpose $purpose,
        public AuthPanel $panel,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $deviceName = null,
        public LoginProvider $provider,
    ) {}

    public static function fromArray(
        array $data,
        AuthPanel $panel,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null,
    ): self {

        return new self(
            identifier: self::cleanIdentifier(
                $data['identifier']
            ),
            provider: isset($data['provider'])
    ? LoginProvider::from(
        (string) $data['provider']
    )
    : LoginProvider::PASSWORD,

            code: self::cleanString(
                $data['code']
            ),

            purpose: OtpPurpose::from(
                (string) $data['purpose']
            ),

            panel: $panel,

            ipAddress: $ipAddress,

            userAgent: $userAgent,

            deviceName: $deviceName,
        );
    }

    private static function cleanIdentifier(
        mixed $identifier
    ): string {

        return strtolower(
            trim((string) $identifier)
        );
    }

    private static function cleanString(
        mixed $value
    ): string {

        return trim(
            (string) $value
        );
    }
}