<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 409
final class InvalidRenewalStateException extends DomainException
{
    public static function alreadyPending(string $documentType): self
    {
        return (new self('A replacement for this document is already waiting for review.'))
            ->withContext(['document_type' => $documentType]);
    }

    public static function notRenewable(string $documentType): self
    {
        return (new self('This document type cannot be renewed.'))
            ->withContext(['document_type' => $documentType]);
    }

    public static function alreadyReviewed(int $renewalId): self
    {
        return (new self('This renewal has already been reviewed.'))
            ->withContext(['renewal_id' => $renewalId]);
    }
}
