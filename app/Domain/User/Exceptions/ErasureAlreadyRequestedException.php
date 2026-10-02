<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;
use Carbon\CarbonInterface;

final class ErasureAlreadyRequestedException extends DomainException
{
    public static function on(CarbonInterface $requestedAt, CarbonInterface $scheduledFor): self
    {
        return (new self('Your account is already scheduled for deletion.'))
            ->withContext([
                'requested_at' => $requestedAt->toIso8601String(),
                'scheduled_for' => $scheduledFor->toIso8601String(),
            ]);
    }

    public static function alreadyErased(): self
    {
        return new self('This account has already been deleted.');
    }
}
