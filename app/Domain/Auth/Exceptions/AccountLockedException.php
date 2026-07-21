<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class AccountLockedException extends RuntimeException
{
    public function __construct(
        public readonly ?int $retryAfter = null,
        public readonly ?string $lockedUntil = null,
    ) {
        parent::__construct(
            'Your account has been temporarily locked due to multiple failed login attempts.',
        );
    }
}