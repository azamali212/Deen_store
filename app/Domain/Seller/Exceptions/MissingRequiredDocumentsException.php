<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 422 — submit refused until all 5 documents are uploaded (C1). The
// missing types travel in context so the API can tell the frontend
// exactly which upload boxes are still empty.
final class MissingRequiredDocumentsException extends DomainException
{
    /**
     * @param  array<int, string>  $missingTypes
     */
    public static function forApplication(int $applicationId, array $missingTypes): self
    {
        return (new self('Please upload all required documents before submitting.'))
            ->withContext([
                'application_id' => $applicationId,
                'missing_documents' => array_values($missingTypes),
            ]);
    }
}
