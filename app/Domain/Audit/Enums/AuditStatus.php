<?php

declare(strict_types=1);

namespace App\Domain\Audit\Enums;

enum AuditStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case DENIED = 'denied';
    case PENDING = 'pending';

    public function isFailure(): bool
    {
        return match ($this) {
            self::FAILED,
            self::DENIED => true,

            default => false,
        };
    }
}
