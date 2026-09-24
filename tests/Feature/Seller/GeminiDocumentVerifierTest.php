<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\DocumentAiStatus;
use App\Domain\Seller\Enums\DocumentRejectionCategory;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Exceptions\DocumentVerificationUnavailableException;
use App\Domain\Seller\Support\GeminiDocumentVerifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The REAL Gemini verifier, with the HTTP layer faked — proves the request
 * shape and, above all, that whatever the model answers is sanitised.
 */
final class GeminiDocumentVerifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => 'test-key',
            'services.gemini.document_verification.model' => 'test-model',
        ]);
    }

    private function geminiAnswers(array|string $answer, int $status = 200): void
    {
        $text = is_array($answer) ? json_encode($answer) : $answer;

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => $text]]],
                ]],
            ], $status),
        ]);
    }

    private function verify(SellerDocumentType $type)
    {
        return (new GeminiDocumentVerifier)->verify($type, 'fake-image-bytes', 'image/png');
    }

    public function test_sends_the_file_inline_to_the_configured_model(): void
    {
        $this->geminiAnswers([
            'document_matches_expected_type' => true,
            'is_explicit_or_offensive' => false,
            'is_readable' => true,
            'fields' => [],
            'tamper_signs' => [],
        ]);

        $this->verify(SellerDocumentType::CNIC_FRONT);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'models/test-model:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && $request['contents'][0]['parts'][1]['inlineData']['mimeType'] === 'image/png'
            && $request['contents'][0]['parts'][1]['inlineData']['data'] === base64_encode('fake-image-bytes'));
    }

    public function test_extracted_fields_are_whitelisted_and_sanitised(): void
    {
        $this->geminiAnswers([
            'document_matches_expected_type' => true,
            'is_explicit_or_offensive' => false,
            'is_readable' => true,
            'fields' => [
                'account_title' => '  Zimal Fabrics  ',
                'bank_name' => 'Meezan Bank',
                // The model ignored "last 4 only" and sent the full number:
                'account_last4' => 'PK36 MEZN 0001 2345 6789 1234',
                'statement_date' => '10/09/2026',           // wrong format
                'full_account_number' => '0001234567891234', // not whitelisted
            ],
            'tamper_signs' => ['Digits in the balance look re-typed', 42, ''],
        ]);

        $result = $this->verify(SellerDocumentType::BANK_STATEMENT);

        $this->assertTrue($result->isAcceptable());
        $this->assertSame('Zimal Fabrics', $result->fields['account_title']);
        $this->assertSame('1234', $result->fields['account_last4']);   // cut to last 4
        $this->assertNull($result->fields['statement_date']);           // invalid date dropped
        $this->assertArrayNotHasKey('full_account_number', $result->fields);
        $this->assertSame(['Digits in the balance look re-typed'], $result->concerns);
        $this->assertSame(DocumentAiStatus::FLAGGED, $result->aiStatus());
    }

    public function test_cnic_number_is_normalised_to_13_digits(): void
    {
        $this->geminiAnswers([
            'document_matches_expected_type' => true,
            'is_explicit_or_offensive' => false,
            'is_readable' => true,
            'fields' => ['cnic_number' => '35202-1234567-1'],
            'tamper_signs' => [],
        ]);

        $this->assertSame('3520212345671', $this->verify(SellerDocumentType::CNIC_BACK)->fields['cnic_number']);
    }

    public function test_explicit_content_is_rejected_even_if_it_claims_to_be_the_right_document(): void
    {
        $this->geminiAnswers([
            'document_matches_expected_type' => true,
            'is_explicit_or_offensive' => true,
            'is_readable' => true,
        ]);

        $result = $this->verify(SellerDocumentType::CNIC_FRONT);

        $this->assertFalse($result->isAcceptable());
        $this->assertSame(DocumentRejectionCategory::EXPLICIT, $result->rejection);
    }

    public function test_a_non_boolean_answer_is_treated_as_the_safer_no(): void
    {
        $this->geminiAnswers([
            'document_matches_expected_type' => 'yes',
            'is_explicit_or_offensive' => false,
            'is_readable' => true,
        ]);

        $this->assertSame(
            DocumentRejectionCategory::WRONG_DOCUMENT,
            $this->verify(SellerDocumentType::CNIC_FRONT)->rejection,
        );
    }

    public function test_unreadable_document_is_rejected(): void
    {
        $this->geminiAnswers([
            'document_matches_expected_type' => true,
            'is_explicit_or_offensive' => false,
            'is_readable' => false,
        ]);

        $this->assertSame(
            DocumentRejectionCategory::UNREADABLE,
            $this->verify(SellerDocumentType::TAX_CERTIFICATE)->rejection,
        );
    }

    public function test_provider_error_throws_unavailable(): void
    {
        $this->geminiAnswers([], 500);

        $this->expectException(DocumentVerificationUnavailableException::class);

        $this->verify(SellerDocumentType::CNIC_FRONT);
    }

    public function test_unparseable_answer_throws_unavailable(): void
    {
        $this->geminiAnswers('this is not json');

        $this->expectException(DocumentVerificationUnavailableException::class);

        $this->verify(SellerDocumentType::CNIC_FRONT);
    }

    public function test_missing_api_key_throws_unavailable_without_calling_google(): void
    {
        config(['services.gemini.api_key' => null]);
        Http::fake();

        try {
            $this->verify(SellerDocumentType::CNIC_FRONT);
            $this->fail('Expected DocumentVerificationUnavailableException.');
        } catch (DocumentVerificationUnavailableException) {
            Http::assertNothingSent();
        }
    }
}
