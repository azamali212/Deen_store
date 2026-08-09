<?php

declare(strict_types=1);

namespace App\Domain\Audit\Enums;

enum AuditSeverity: string
{
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case CRITICAL = 'critical';

    public function priority(): int
    {
        return match ($this) {
            self::INFO => 10,
            self::NOTICE => 20,
            self::WARNING => 30,
            self::CRITICAL => 40,
        };
    }

    public function isHighRisk(): bool
    {
        return match ($this) {
            self::WARNING,
            self::CRITICAL => true,
            default => false,
        };
    }
}
