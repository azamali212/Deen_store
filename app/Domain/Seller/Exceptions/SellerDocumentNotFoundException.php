<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Exceptions\DomainException;

// 404 — admin asked to download a document type that was never uploaded,
// or whose file is missing from storage.
final class SellerDocumentNotFoundException extends DomainException
{
    public static function forType(int $applicationId, SellerDocumentType $type): self
    {
        return (new self("The {$type->label()} document has not been uploaded for application #{$applicationId}."))
            ->withContext([
                'application_id' => $applicationId,
                'document_type' => $type->value,
            ]);
    }

    public static function fileMissing(): self
    {
        return new self('The document file could not be found in storage.');
    }

    /** P9-1 — the file is gone on purpose; the record of it is not. */
    public static function purged(): self
    {
        return new self('This document was deleted under our retention policy. The record of it is kept in the audit log.');
    }
}
