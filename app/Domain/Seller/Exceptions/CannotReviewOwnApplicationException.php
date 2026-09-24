<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 403 — segregation of duties: an admin who also applied as a seller must
// never be able to approve their own business.
final class CannotReviewOwnApplicationException extends DomainException
{
    public static function forReviewer(int $reviewerId, int $applicationId): self
    {
        return (new self('You cannot review your own seller application. Another admin must review it.'))
            ->withContext([
                'reviewer_id' => $reviewerId,
                'application_id' => $applicationId,
            ]);
    }
}
