<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

/**
 * How a country identifies a bank account. Not a list of banks — a list of
 * SHAPES, because that is what changes between countries.
 */
enum BankAccountFormat: string
{
    /** One field: an IBAN, checked with mod-97 (P11-3). */
    case IBAN = 'iban';

    /**
     * Two fields: a branch identifier plus an account number. The UK's
     * sort code and the US routing number are the same idea with
     * different digit counts (P11-1).
     */
    case BRANCH_AND_ACCOUNT = 'branch_and_account';

    /** P11-4 — somewhere we have no rules for: 8-24 characters. */
    case BASIC = 'basic';

    public function needsBranchCode(): bool
    {
        return $this === self::BRANCH_AND_ACCOUNT;
    }
}
