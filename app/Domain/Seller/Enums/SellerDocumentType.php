<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerDocumentType: string
{
    case CNIC_FRONT = 'cnic_front';
    case CNIC_BACK = 'cnic_back';
    case BUSINESS_LICENSE = 'business_license';
    case TAX_CERTIFICATE = 'tax_certificate';
    case BANK_STATEMENT = 'bank_statement';

    /**
     * Every type is required before submit — MissingRequiredDocumentsException
     * compares the uploaded types against this list.
     *
     * @return array<int, string>
     */
    public static function required(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * P8-1 — the name of the AI-extracted field holding this document's
     * expiry date, or null when it has none. Kept here so the verifier's
     * FIELDS whitelist and the expiry logic can never drift apart.
     */
    public function expiryField(): ?string
    {
        return match ($this) {
            self::CNIC_FRONT => 'date_of_expiry',
            self::BUSINESS_LICENSE => 'expiry_date',
            default => null,
        };
    }

    /** The plain, queryable seller_profiles column that date is copied to. */
    public function expiryColumn(): ?string
    {
        return match ($this) {
            self::CNIC_FRONT => 'cnic_expires_at',
            self::BUSINESS_LICENSE => 'licence_expires_at',
            default => null,
        };
    }

    /**
     * Documents a LIVE seller may replace. The tax certificate and bank
     * statement are not here: the first never expires, the second has its
     * own proof flow (P6-2).
     *
     * @return array<int, self>
     */
    public static function renewable(): array
    {
        return [self::CNIC_FRONT, self::CNIC_BACK, self::BUSINESS_LICENSE];
    }

    public function isRenewable(): bool
    {
        return in_array($this, self::renewable(), strict: true);
    }

    public function label(): string
    {
        return match ($this) {
            self::CNIC_FRONT => 'CNIC (Front)',
            self::CNIC_BACK => 'CNIC (Back)',
            self::BUSINESS_LICENSE => 'Business License',
            self::TAX_CERTIFICATE => 'Tax Certificate',
            self::BANK_STATEMENT => 'Bank Statement',
        };
    }
}
