<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

use Carbon\CarbonInterface;

final readonly class CreateTrustedDeviceData
{
    public function __construct(
        public string $userId,
        public string $fingerprint,
        public ?string $deviceName = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?CarbonInterface $trustedUntil = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'fingerprint' => $this->fingerprint,
            'device_name' => $this->deviceName,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'trusted_until' => $this->trustedUntil,
            'last_used_at' => now(),
        ], static fn ($value): bool => $value !== null);
    }
}