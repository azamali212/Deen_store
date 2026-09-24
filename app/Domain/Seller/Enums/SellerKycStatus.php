<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

/**
 * P8-3 — "is this seller's identity still valid?".
 *
 * Deliberately SEPARATE from BankVerificationStatus, which answers a
 * different question ("is this bank account proven to be theirs?"). One
 * column per fact: overloading the bank status with expiry would destroy
 * the bank status on expiry, and re-KYC would have to guess what to
 * restore afterwards.
 */
enum SellerKycStatus: string
{
    case VALID = 'valid';
    case EXPIRING_SOON = 'expiring_soon';
    case EXPIRED = 'expired';

    /**
     * P8-2 — an expired seller keeps selling but cannot be PAID until a
     * fresh document is approved. The money is safe either way; a seller
     * who missed an email does not lose their business over it.
     */
    public function blocksPayout(): bool
    {
        return $this === self::EXPIRED;
    }

    public function label(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::EXPIRING_SOON => 'Expiring soon',
            self::EXPIRED => 'Expired',
        };
    }
}
