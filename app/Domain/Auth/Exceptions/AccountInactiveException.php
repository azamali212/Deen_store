<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

final class AccountInactiveException extends AuthException
{
    public function __construct(
        string $message = 'Your account is inactive.'
    ) {
        parent::__construct($message, 403);
    }
}