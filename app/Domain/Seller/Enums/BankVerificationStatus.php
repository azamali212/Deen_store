<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

/**
 * Payout bank vs the bank statement verified during onboarding (P5-2),
 * and the way out of a mismatch (P6-2).
 *
 *   matched        AI matched it against the onboarding statement
 *   admin_verified an admin confirmed it from an uploaded proof
 *   pending_review seller uploaded proof, waiting for an admin
 *   mismatch       does not match, no accepted proof
 *   unknown        nothing to compare with (AI was off during onboarding)
 */
enum BankVerificationStatus: string
{
    case MATCHED = 'matched';
    case ADMIN_VERIFIED = 'admin_verified';
    case PENDING_REVIEW = 'pending_review';
    case MISMATCH = 'mismatch';
    case UNKNOWN = 'unknown';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // The only two states the future Payouts domain may pay out to.
    public function isPayoutReady(): bool
    {
        return $this === self::MATCHED || $this === self::ADMIN_VERIFIED;
    }

    // Uploading proof only makes sense when it isn't already sorted.
    public function acceptsProof(): bool
    {
        return $this === self::MISMATCH || $this === self::UNKNOWN;
    }

    public function label(): string
    {
        return match ($this) {
            self::MATCHED => 'Matched onboarding statement',
            self::ADMIN_VERIFIED => 'Verified by an admin',
            self::PENDING_REVIEW => 'Proof uploaded — awaiting admin review',
            self::MISMATCH => 'Does not match the verified statement',
            self::UNKNOWN => 'Not verified',
        };
    }
}
