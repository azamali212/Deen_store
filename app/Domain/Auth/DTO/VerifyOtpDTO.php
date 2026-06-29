<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Enums\OtpPurpose;

final readonly class VerifyOtpDTO
{
    public function __construct(
        public string $identifier,
        public string $code,
        public OtpPurpose $purpose,
        public AuthPanel $panel,
        public LoginProvider $provider,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
    ) {}

    public static function fromArray(
        array $data,
        AuthPanel $panel,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null,
    ): self {

        return new self(
            identifier: strtolower(trim((string) $data['identifier'])),

            code: trim((string) $data['code']),

            purpose: match ($panel) {

                AuthPanel::ADMIN
                    => OtpPurpose::ADMIN_LOGIN,

                AuthPanel::CUSTOMER,
                AuthPanel::SELLER
                    => OtpPurpose::LOGIN,

            },

            panel: $panel,

            provider: LoginProvider::PASSWORD,

            ipAddress: $ipAddress,

            userAgent: $userAgent,

            deviceName: $deviceName,
        );
    }
}