<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

// One row of the Layer 2 cross-check report.
enum VerificationCheckResult: string
{
    case PASS = 'pass';
    case WARN = 'warn';    // flagged for the admin, does not block
    case FAIL = 'fail';    // hard error — blocks submit (P5-1)
    case SKIPPED = 'skipped';
}
