<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerDocumentType: string
{
    // Identity — Pakistan
    case CNIC_FRONT = 'cnic_front';
    case CNIC_BACK = 'cnic_back';

    // Identity — everywhere. A passport is the one identity document that
    // exists in every country, which is why it is the fallback (P10-4).
    case PASSPORT = 'passport';

    // Identity — UK and much of Europe
    case DRIVING_LICENCE_FRONT = 'driving_licence_front';
    case DRIVING_LICENCE_BACK = 'driving_licence_back';
    case NATIONAL_ID_FRONT = 'national_id_front';
    case NATIONAL_ID_BACK = 'national_id_back';

    // Business — the same three everywhere, only the local name differs
    case BUSINESS_LICENSE = 'business_license';
    case TAX_CERTIFICATE = 'tax_certificate';
    case BANK_STATEMENT = 'bank_statement';

    /**
     * Which documents prove WHO the person is. The rest prove things about
     * the business.
     */
    public function isIdentity(): bool
    {
        return in_array($this, [
            self::CNIC_FRONT, self::CNIC_BACK,
            self::PASSPORT,
            self::DRIVING_LICENCE_FRONT, self::DRIVING_LICENCE_BACK,
            self::NATIONAL_ID_FRONT, self::NATIONAL_ID_BACK,
        ], strict: true);
    }

    /**
     * P10-3 — the FRONT half of a two-sided identity document, i.e. the
     * side that carries the name and the expiry date. A passport has one
     * side, so it is its own front.
     */
    public function isIdentityFront(): bool
    {
        return in_array($this, [
            self::CNIC_FRONT, self::PASSPORT,
            self::DRIVING_LICENCE_FRONT, self::NATIONAL_ID_FRONT,
        ], strict: true);
    }

    /** The matching back, when the document has one. */
    public function identityBack(): ?self
    {
        return match ($this) {
            self::CNIC_FRONT => self::CNIC_BACK,
            self::DRIVING_LICENCE_FRONT => self::DRIVING_LICENCE_BACK,
            self::NATIONAL_ID_FRONT => self::NATIONAL_ID_BACK,
            default => null,
        };
    }

    /**
     * P8-1 — the AI-extracted field holding this document's expiry date,
     * or null when it has none.
     */
    public function expiryField(): ?string
    {
        return match ($this) {
            self::CNIC_FRONT, self::PASSPORT,
            self::DRIVING_LICENCE_FRONT, self::NATIONAL_ID_FRONT => 'date_of_expiry',
            self::BUSINESS_LICENSE => 'expiry_date',
            default => null,
        };
    }

    /**
     * The plain, queryable seller_profiles column that date is copied to.
     * P10-5 — identity_expires_at, not cnic_expires_at: the same column now
     * holds a passport's expiry for a seller who has no CNIC.
     */
    public function expiryColumn(): ?string
    {
        return match ($this) {
            self::CNIC_FRONT, self::PASSPORT,
            self::DRIVING_LICENCE_FRONT, self::NATIONAL_ID_FRONT => 'identity_expires_at',
            self::BUSINESS_LICENSE => 'licence_expires_at',
            default => null,
        };
    }

    /**
     * Documents a LIVE seller may replace. Identity documents expire, and
     * so do business licences. The tax certificate does not, and the bank
     * statement has its own proof flow (P6-2).
     *
     * @return array<int, self>
     */
    public static function renewable(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type): bool => $type->isIdentity() || $type === self::BUSINESS_LICENSE,
        ));
    }

    public function isRenewable(): bool
    {
        return in_array($this, self::renewable(), strict: true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CNIC_FRONT => 'CNIC (Front)',
            self::CNIC_BACK => 'CNIC (Back)',
            self::PASSPORT => 'Passport',
            self::DRIVING_LICENCE_FRONT => 'Driving Licence (Front)',
            self::DRIVING_LICENCE_BACK => 'Driving Licence (Back)',
            self::NATIONAL_ID_FRONT => 'National ID Card (Front)',
            self::NATIONAL_ID_BACK => 'National ID Card (Back)',
            self::BUSINESS_LICENSE => 'Business Registration',
            self::TAX_CERTIFICATE => 'Tax Registration Certificate',
            self::BANK_STATEMENT => 'Bank Statement',
        };
    }
}
