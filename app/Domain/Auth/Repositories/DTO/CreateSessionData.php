<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

use App\Domain\Auth\Enums\AuthPanel;

final readonly class CreateSessionData
{
    public function __construct(
        public string $userId,
        public string $tokenId,
        public AuthPanel $panel,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $deviceName = null,
        public ?string $browser = null,
        public ?string $operatingSystem = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'token_id' => $this->tokenId,
            'panel' => $this->panel->value,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_name' => $this->deviceName,
            'browser' => $this->browser,
            'operating_system' => $this->operatingSystem,
            'last_activity_at' => now(),
        ], static fn ($value): bool => $value !== null);
    }
}