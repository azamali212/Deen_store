<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\DTO;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginProvider;
use App\Domain\Auth\Enums\LoginRiskLevel;

final readonly class CreateLoginLogData
{
    public function __construct(
        public AuthStatus $status,
        public AuthPanel $panel,
        public LoginProvider $provider,
        public LoginRiskLevel $riskLevel,
        public ?string $userId = null,
        public ?string $email = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $deviceName = null,
        public ?string $failureReason = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'email' => $this->email,
            'status' => $this->status->value,
            'panel' => $this->panel->value,
            'provider' => $this->provider->value,
            'risk_level' => $this->riskLevel->value,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'device_name' => $this->deviceName,
            'failure_reason' => $this->failureReason,
            'metadata' => $this->metadata === [] ? null : $this->metadata,
        ], static fn ($value): bool => $value !== null);
    }
}