<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Exceptions;

use App\Exceptions\DomainException;

final class ModerationCheckFailedException extends DomainException
{
    public static function apiKeyMissing(): self
    {
        return new self('Profile moderation is not configured — GEMINI_API_KEY is missing.');
    }

    public static function providerError(string $reason): self
    {
        return (new self('The AI moderation provider failed: '.$reason))->withContext(['reason' => $reason]);
    }
}
