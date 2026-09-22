<?php

declare(strict_types=1);

namespace App\Domain\Audit\Exceptions;

use App\Exceptions\DomainException;

final class AiSummaryUnavailableException extends DomainException
{
    public static function apiKeyMissing(): self
    {
        return new self('AI summary is not configured — GEMINI_API_KEY is missing.');
    }

    public static function noActivity(): self
    {
        return new self('This user has no audit activity in the requested time range.');
    }

    public static function providerError(string $reason): self
    {
        return (new self('The AI provider could not generate a summary: '.$reason))
            ->withContext(['reason' => $reason]);
    }
}
