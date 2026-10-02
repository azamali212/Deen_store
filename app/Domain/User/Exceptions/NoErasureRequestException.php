<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class NoErasureRequestException extends DomainException
{
    public static function forUser(int $userId): self
    {
        return (new self('There is no pending deletion request on this account.'))
            ->withContext(['user_id' => $userId]);
    }
}
