<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

final class SuspiciousLoginException extends AuthException
{
    public function __construct(
        string $message = 'Suspicious login detected.'
    ) {
        parent::__construct($message, 423);
    }
}