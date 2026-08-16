<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\LoginProvider;

final readonly class VerifyTwoFactorDTO
{
    public function __construct(
        public string $identifier,
        public string $code,

        public AuthPanel $panel,

        public LoginProvider $provider,

        public string $ipAddress,

        public ?string $userAgent,

        public ?string $deviceName,
    ) {}
}