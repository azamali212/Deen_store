<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Exceptions;

use App\Exceptions\DomainException;

final class ModerationFlagAlreadyResolvedException extends DomainException
{
    public static function forFlag(int $flagId): self
    {
        return (new self("Moderation flag #{$flagId} has already been reviewed."))
            ->withContext(['flag_id' => $flagId]);
    }
}
