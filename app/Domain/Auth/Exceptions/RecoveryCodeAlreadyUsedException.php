<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use RuntimeException;

final class RecoveryCodeAlreadyUsedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'This recovery code has already been used.',
        );
    }
}