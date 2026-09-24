<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Exceptions\DomainException;

// 422 — submit blocked by a hard cross-check failure (P5-1), e.g. CNIC
// front/back numbers differ or the CNIC has expired.
final class ApplicationFailedAiChecksException extends DomainException
{
    /**
     * @param  array<int, string>  $failures  customer-facing messages
     */
    public static function withFailures(int $applicationId, array $failures): self
    {
        return (new self('Your documents did not pass the automated checks. Please fix the issues below and submit again.'))
            ->withContext([
                'application_id' => $applicationId,
                'failed_checks' => array_values($failures),
            ]);
    }
}
