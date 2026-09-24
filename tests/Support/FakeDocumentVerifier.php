<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\DTO\DocumentVerificationResultDTO;
use App\Domain\Seller\Enums\SellerDocumentType;
use Throwable;

/**
 * Test double for Layer 1 — never calls any AI. By default every document
 * "passes" with fields that are CONSISTENT with each other (same CNIC on
 * both sides, names matching the applicant/business), so a test only has
 * to override the one thing it wants to break.
 */
final class FakeDocumentVerifier implements DocumentVerifierInterface
{
    /** @var array<string, DocumentVerificationResultDTO> */
    private array $overrides = [];

    private ?Throwable $failure = null;

    /** @var array<int, string> every type verify() was called with */
    public array $verifiedTypes = [];

    public function __construct(
        public readonly string $personName = 'Azam Ali',
        public readonly string $businessName = 'Zimal Fabrics Pvt Ltd',
        public readonly string $cnicNumber = '3520212345671',
        public readonly string $accountLast4 = '1234',
    ) {}

    public function returnFor(SellerDocumentType $type, DocumentVerificationResultDTO $result): self
    {
        $this->overrides[$type->value] = $result;

        return $this;
    }

    public function failWith(Throwable $failure): self
    {
        $this->failure = $failure;

        return $this;
    }

    public function verify(SellerDocumentType $type, string $bytes, string $mimeType): DocumentVerificationResultDTO
    {
        $this->verifiedTypes[] = $type->value;

        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->overrides[$type->value]
            ?? DocumentVerificationResultDTO::accepted($this->fieldsFor($type));
    }

    /**
     * @return array<string, string|null>
     */
    public function fieldsFor(SellerDocumentType $type): array
    {
        return match ($type) {
            SellerDocumentType::CNIC_FRONT => [
                'full_name' => $this->personName,
                'father_name' => 'Muhammad Ayub',
                'cnic_number' => $this->cnicNumber,
                'date_of_birth' => '1995-05-10',
                'date_of_expiry' => now()->addYears(5)->toDateString(),
            ],
            SellerDocumentType::CNIC_BACK => [
                'cnic_number' => $this->cnicNumber,
            ],
            SellerDocumentType::BUSINESS_LICENSE => [
                'business_name' => $this->businessName,
                'registration_number' => 'REG-12345',
                'expiry_date' => now()->addYear()->toDateString(),
            ],
            SellerDocumentType::TAX_CERTIFICATE => [
                'registered_name' => $this->businessName,
                'ntn' => '1234567-8',
            ],
            SellerDocumentType::BANK_STATEMENT => [
                'account_title' => $this->businessName,
                'bank_name' => 'Meezan Bank',
                'account_last4' => $this->accountLast4,
                'statement_date' => now()->subDays(10)->toDateString(),
            ],
        };
    }
}
