<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events\Data;

use App\Domain\Auth\Enums\AuthPanel;
use Carbon\CarbonInterface;

final readonly class LogoutEventData
{
    public function __construct(
        public string $userId,
        public string $email,
        public AuthPanel $panel,
        public ?string $sessionId,
        public bool $logoutAllDevices,
        public ?string $ipAddress,
        public ?string $userAgent,
        public CarbonInterface $occurredAt,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'email' => $this->email,
            'panel' => $this->panel->value,
            'session_id' => $this->sessionId,
            'logout_all_devices' => $this->logoutAllDevices,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'occurred_at' => $this->occurredAt,
        ];
    }
}