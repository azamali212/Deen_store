<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Data\CountryDocumentMap;
use App\Domain\Seller\DTO\VerificationReportDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\VerificationCheckResult;
use App\Domain\Seller\Support\NameMatcher;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use App\Models\SellerProfile;

/**
 * Layer 2 (submit-time cross-checks) + P5-2 (payout bank match).
 *
 * NO AI call happens here. Everything compares fields the AI already read
 * in Layer 1 — plain PHP is deterministic, testable, and can't be talked
 * into a different answer by text hidden inside a document.
 *
 * Messages of FAIL checks are shown to the CUSTOMER, so they never contain
 * names or numbers. WARN messages are ADMIN-only and may.
 */
final readonly class SellerDocumentVerificationService
{
    private const STATEMENT_MAX_AGE_DAYS = 90;

    public function __construct(
        private NameMatcher $names,
        private SellerDocumentRequirements $requirements,
    ) {}

    public function crossCheck(SellerApplication $application): VerificationReportDTO
    {
        $application->loadMissing(['documents', 'user']);

        $documents = $application->documents->keyBy(
            fn (SellerApplicationDocument $document): string => $document->document_type->value,
        );

        $country = (string) ($application->country ?? 'PK');
        $uploaded = $documents->keys()->all();

        // C54 — over the types THIS application requires, never over the
        // whole enum. With per-country rules most types are legitimately
        // absent, and the old loop switched Layer 2 off for every seller
        // outside Pakistan by finding a "missing" German CNIC.
        $required = $this->requirements->requiredTypes($country, $uploaded);

        foreach ($required as $type) {
            $document = $documents->get($type->value);

            if ($document === null || $document->ai_status === null || $document->ai_status === DocumentAiStatus::SKIPPED) {
                return VerificationReportDTO::skipped();
            }
        }

        $fields = fn (SellerDocumentType $type): array => $documents->get($type->value)?->aiFields() ?? [];

        $applicantName = (string) $application->user->name;
        $businessName = (string) $application->business_name;
        $today = now()->toDateString();

        $checks = [];

        // C53 — which identity document did they actually use? A passport
        // has one side; a CNIC has two. The checks below follow whichever
        // option they completed rather than assuming a CNIC.
        $identity = $this->requirements->identityOptionUsed($country, $uploaded) ?? [];
        $identityFront = null;
        $identityBack = null;

        foreach ($identity as $type) {
            if ($type->isIdentityFront()) {
                $identityFront = $type;
            } else {
                $identityBack = $type;
            }
        }

        if ($identityFront === null) {
            return VerificationReportDTO::skipped();
        }

        $front = $fields($identityFront);
        $license = $fields(SellerDocumentType::BUSINESS_LICENSE);
        $tax = $fields(SellerDocumentType::TAX_CERTIFICATE);
        $bank = $fields(SellerDocumentType::BANK_STATEMENT);

        // 1. Both sides of the SAME document — HARD (P5-1), and only when
        //    the document HAS two sides.
        if ($identityBack !== null) {
            $frontNumber = $front['identity_number'] ?? null;
            $backNumber = $fields($identityBack)['identity_number'] ?? null;

            $checks[] = match (true) {
                $frontNumber === null => $this->check('identity_numbers_match', VerificationCheckResult::WARN,
                    'The number on the front side could not be read — compare both sides manually.'),
                $backNumber === null => $this->check('identity_numbers_match', VerificationCheckResult::WARN,
                    'The number on the back side could not be read — compare both sides manually.'),
                $frontNumber !== $backNumber => $this->check('identity_numbers_match', VerificationCheckResult::FAIL,
                    'The numbers on the front and back do not match. Please upload both sides of the SAME document.'),
                default => $this->check('identity_numbers_match', VerificationCheckResult::PASS,
                    'The number is the same on both sides.'),
            };
        }

        // 1b. C52 — a country with a FIXED identity-number length says so
        //     in CountryDocumentMap. Nowhere else knows or cares.
        $expectedDigits = CountryDocumentMap::identityNumberDigits($country);
        $number = $front['identity_number'] ?? null;

        if ($expectedDigits !== null && $number !== null) {
            $digits = (string) preg_replace('/\D/', '', $number);

            $checks[] = strlen($digits) === $expectedDigits
                ? $this->check('identity_number_format', VerificationCheckResult::PASS,
                    'The identity number has the expected length.')
                : $this->check('identity_number_format', VerificationCheckResult::WARN,
                    sprintf('The identity number read from the document is %d digits, expected %d.', strlen($digits), $expectedDigits));
        }

        // 2. Identity document expiry — HARD (P5-1)
        $expiry = $front['date_of_expiry'] ?? null;

        $checks[] = match (true) {
            $expiry === null => $this->check('identity_not_expired', VerificationCheckResult::WARN,
                'The expiry date could not be read — check it manually.'),
            $expiry < $today => $this->check('identity_not_expired', VerificationCheckResult::FAIL,
                'Your identity document has expired. Please upload a valid one.'),
            default => $this->check('identity_not_expired', VerificationCheckResult::PASS,
                'Identity document is valid until '.$expiry.'.'),
        };

        // 3. Name on the identity document vs the account — flag only
        $identityName = $front['full_name'] ?? null;

        $checks[] = $this->names->matches($identityName, $applicantName)
            ? $this->check('identity_name_matches_account', VerificationCheckResult::PASS,
                'Name on the identity document matches the account name.')
            : $this->check('identity_name_matches_account', VerificationCheckResult::WARN,
                sprintf('Name on the identity document ("%s") does not clearly match the account name ("%s").', $identityName ?? 'unreadable', $applicantName));

        // 4. Business licence name + expiry — flag only
        $licenseName = $license['business_name'] ?? null;

        $checks[] = $this->names->matches($licenseName, $businessName)
            ? $this->check('license_business_name', VerificationCheckResult::PASS,
                'Business name on the licence matches the application.')
            : $this->check('license_business_name', VerificationCheckResult::WARN,
                sprintf('Business name on the licence ("%s") does not clearly match the application ("%s").', $licenseName ?? 'unreadable', $businessName));

        $licenseExpiry = $license['expiry_date'] ?? null;

        if ($licenseExpiry !== null && $licenseExpiry < $today) {
            $checks[] = $this->check('license_not_expired', VerificationCheckResult::WARN,
                'The business licence appears to have expired on '.$licenseExpiry.'.');
        }

        // 5. Tax certificate name — business OR applicant (sole proprietors
        //    register tax in their own name) — flag only
        $taxName = $tax['registered_name'] ?? null;

        $checks[] = $this->names->matches($taxName, $businessName) || $this->names->matches($taxName, $applicantName)
            ? $this->check('tax_certificate_name', VerificationCheckResult::PASS,
                'Name on the tax certificate matches the business or the applicant.')
            : $this->check('tax_certificate_name', VerificationCheckResult::WARN,
                sprintf('Name on the tax certificate ("%s") matches neither the business nor the applicant.', $taxName ?? 'unreadable'));

        // 6. Bank statement title — business OR applicant — flag only
        $bankTitle = $bank['account_title'] ?? null;

        $checks[] = $this->names->matches($bankTitle, $businessName) || $this->names->matches($bankTitle, $applicantName)
            ? $this->check('bank_statement_title', VerificationCheckResult::PASS,
                'Bank statement account title matches the business or the applicant.')
            : $this->check('bank_statement_title', VerificationCheckResult::WARN,
                sprintf('Bank statement title ("%s") matches neither the business nor the applicant.', $bankTitle ?? 'unreadable'));

        // 7. Bank statement recency — flag only
        $statementDate = $bank['statement_date'] ?? null;
        $oldestAllowed = now()->subDays(self::STATEMENT_MAX_AGE_DAYS)->toDateString();

        $checks[] = match (true) {
            $statementDate === null => $this->check('bank_statement_recent', VerificationCheckResult::WARN,
                'Bank statement date could not be read.'),
            $statementDate < $oldestAllowed => $this->check('bank_statement_recent', VerificationCheckResult::WARN,
                'Bank statement is older than '.self::STATEMENT_MAX_AGE_DAYS.' days ('.$statementDate.').'),
            default => $this->check('bank_statement_recent', VerificationCheckResult::PASS,
                'Bank statement is recent ('.$statementDate.').'),
        };

        // 8. Possible tampering, per document — flag only (never auto-block:
        //    AI tamper detection is a hint, not proof)
        foreach ($required as $type) {
            foreach ($documents->get($type->value)?->aiConcerns() ?? [] as $index => $concern) {
                $checks[] = $this->check("tamper_{$type->value}_{$index}", VerificationCheckResult::WARN,
                    'Possible editing on '.$type->label().': '.$concern);
            }
        }

        return VerificationReportDTO::fromChecks($checks);
    }

    /**
     * P5-2 — does the payout account the seller just entered match the
     * bank statement verified during onboarding? (last 4 + account title)
     */
    public function matchPayoutBank(SellerProfile $profile, string $newLast4, ?string $accountTitle): BankVerificationStatus
    {
        $statement = $profile->application
            ?->documents()
            ->where('document_type', SellerDocumentType::BANK_STATEMENT->value)
            ->first();

        if ($statement === null
            || $statement->ai_status === null
            || $statement->ai_status === DocumentAiStatus::SKIPPED) {
            return BankVerificationStatus::UNKNOWN;
        }

        $statementLast4 = $statement->aiFields()['account_last4'] ?? null;

        if ($statementLast4 === null) {
            return BankVerificationStatus::UNKNOWN;
        }

        $matches = $statementLast4 === $newLast4
            && $this->names->matches($accountTitle, $statement->aiFields()['account_title'] ?? null);

        return $matches ? BankVerificationStatus::MATCHED : BankVerificationStatus::MISMATCH;
    }

    // Same loose comparison the cross-checks use (P6-2 reuses it).
    public function titlesMatch(?string $a, ?string $b): bool
    {
        return $this->names->matches($a, $b);
    }

    /**
     * @return array{key: string, result: string, message: string}
     */
    private function check(string $key, VerificationCheckResult $result, string $message): array
    {
        return [
            'key' => $key,
            'result' => $result->value,
            'message' => $message,
        ];
    }
}
