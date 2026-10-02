<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

/**
 * P12-5 — the four things a user is ever asked to agree to.
 *
 * The split that matters here is WITHDRAWABLE vs not. Terms and privacy
 * policy are the basis on which the account exists at all; there is no
 * coherent state where someone has an account but has withdrawn the terms.
 * Asking to withdraw those is asking to close the account, which is what
 * the erasure endpoint is for — so the API says that instead of pretending
 * to toggle something off.
 *
 * Marketing and document processing are genuinely optional, so GDPR Art.
 * 7(3) applies in full: withdrawing must be as easy as agreeing was.
 */
enum ConsentType: string
{
    case TERMS = 'terms';
    case PRIVACY_POLICY = 'privacy_policy';
    case MARKETING = 'marketing';
    case DOCUMENT_PROCESSING = 'document_processing';

    public function isWithdrawable(): bool
    {
        return match ($this) {
            self::MARKETING, self::DOCUMENT_PROCESSING => true,
            self::TERMS, self::PRIVACY_POLICY => false,
        };
    }

    /**
     * Whether a brand-new account must have this before it can be used.
     */
    public function isRequired(): bool
    {
        return ! $this->isWithdrawable();
    }

    public function label(): string
    {
        return match ($this) {
            self::TERMS => 'Terms of service',
            self::PRIVACY_POLICY => 'Privacy policy',
            self::MARKETING => 'Marketing emails',
            self::DOCUMENT_PROCESSING => 'Automated checking of identity documents',
        };
    }

    /**
     * What this consent covers, in the words the user sees. Written plainly
     * on purpose: consent obtained through wording nobody understands is not
     * consent (GDPR Art. 7(2)).
     */
    public function description(): string
    {
        return match ($this) {
            self::TERMS => 'The rules for using the marketplace, as a buyer and as a seller.',
            self::PRIVACY_POLICY => 'What personal data we hold, why we hold it, and how long we keep it.',
            self::MARKETING => 'Offers, new arrivals and seller news by email. Never required to use the account.',
            self::DOCUMENT_PROCESSING => 'Letting an automated system read the identity documents you upload, to check them before a human reviewer sees them. Refusing means a person reviews them instead, which takes longer.',
        };
    }

    /**
     * The wording currently in force. A consent row stores the version that
     * was live when it was given, never this — that is the whole point.
     */
    public function currentVersion(): string
    {
        $version = config('privacy.consent_versions.'.$this->value);

        return is_string($version) && $version !== '' ? $version : 'unversioned';
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
