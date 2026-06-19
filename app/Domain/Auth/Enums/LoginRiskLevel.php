<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

enum LoginRiskLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    // This method determines if the login risk level requires OTP verification.
    public function requiresOtp(): bool
    {
        return in_array($this, [
            self::MEDIUM,
            self::HIGH,
            self::CRITICAL,
        ], true);
    }

    public function shouldBlock(): bool
    {
        return $this === self::CRITICAL;
    }
}