<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

final readonly class AuditRedactionService
{
    private const REDACTED = '[REDACTED]';

    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'api_key',
        'api_secret',
        'secret',
        'otp',
        'otp_code',
        'verification_code',
        'recovery_code',
        'recovery_codes',
        'credit_card',
        'card_number',
        'cvv',
        'cvc',
    ];

    public function redact(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return $this->sanitize($data);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $data[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalizedKey = strtolower(
            str_replace(
                ['-', ' '],
                '_',
                trim($key),
            ),
        );

        return in_array(
            $normalizedKey,
            self::SENSITIVE_KEYS,
            true,
        );
    }
}
