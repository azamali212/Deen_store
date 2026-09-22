<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Exceptions;

use App\Domain\Moderation\DTO\ModerationCheckResultDTO;
use App\Exceptions\DomainException;

final class ProfileContentRejectedException extends DomainException
{
    public static function forResult(ModerationCheckResultDTO $result): self
    {
        return (new self($result->summary))->withContext([
            'severity' => $result->severity?->value,
            'flagged_fields' => $result->flaggedFields,
        ]);
    }
}
