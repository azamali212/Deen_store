<?php

declare(strict_types=1);

namespace App\Domain\Auth\AI;

use App\Domain\Auth\Enums\AuthPanel;
use App\Models\User;

final class AuthRiskPromptBuilder
{
    public function build(
        User $user,
        AuthPanel $panel,
        bool $trustedDevice,
        bool $newIp,
        bool $newCountry,
        int $failedAttempts,
        ?string $ipAddress = null,
        ?string $deviceName = null,
    ): string {

        return <<<PROMPT
You are a cybersecurity risk engine.

Analyze the following login attempt.

User ID: {$user->id}
Email: {$user->email}

Panel: {$panel->value}

Trusted Device: {$this->boolean($trustedDevice)}
New IP Address: {$this->boolean($newIp)}
New Country: {$this->boolean($newCountry)}

Failed Login Attempts: {$failedAttempts}

IP Address: {$ipAddress}

Device Name: {$deviceName}

Return JSON only.

{
  "risk_score": integer,
  "risk_level": "low|medium|high",
  "requires_otp": boolean,
  "should_block": boolean,
  "reason": "string"
}
PROMPT;
    }

    private function boolean(
        bool $value
    ): string {
        return $value
            ? 'Yes'
            : 'No';
    }
}