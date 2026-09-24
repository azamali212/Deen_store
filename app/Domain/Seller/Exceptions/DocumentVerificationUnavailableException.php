<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 503 — the AI provider is down or not configured. Nothing is stored.
// The detailed reason is logged; users only see a generic message.
final class DocumentVerificationUnavailableException extends DomainException
{
    public static function notConfigured(): self
    {
        return new self('Document verification is enabled but GEMINI_API_KEY is missing.');
    }

    public static function providerError(string $reason): self
    {
        return (new self('The document verification provider failed: '.$reason))
            ->withContext(['reason' => $reason]);
    }
}
