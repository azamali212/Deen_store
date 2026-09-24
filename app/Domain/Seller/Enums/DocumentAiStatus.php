<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

/**
 * Per-document AI result. There is no REJECTED case on purpose: a document
 * the AI rejects is never stored, so it can never appear in the database.
 */
enum DocumentAiStatus: string
{
    case PASSED = 'passed';    // AI read it, nothing suspicious
    case FLAGGED = 'flagged';  // accepted, but with concerns for the admin
    case SKIPPED = 'skipped';  // AI verification switched off -> manual review

    public function label(): string
    {
        return match ($this) {
            self::PASSED => 'Passed automated check',
            self::FLAGGED => 'Flagged for admin attention',
            self::SKIPPED => 'Not checked (manual review)',
        };
    }
}
