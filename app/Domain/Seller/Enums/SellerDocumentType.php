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
