<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Models\SellerApplication;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesDocumentVerification;
use Tests\TestCase;

/**
 * A3 — identity documents per country (BLUEPRINT section 16).
 */
final class SellerCountryDocumentsTest extends TestCase
{
    use RefreshDatabase;
    use FakesDocumentVerification;

    private const BASE = '/api/v1/seller/application';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        return $user;
    }

    private function apply(string $country, string $storeName = 'Zimal Fabrics')
    {
        return $this->postJson(self::BASE, [
            'store_name' => $storeName,
            'business_name' => $storeName.' Ltd',
            'business_type' => 'company',
            'country' => $country,
            'accept_document_processing' => true,
        ]);
    }

    private function upload(SellerDocumentType $type)
    {
        return $this->postJson(self::BASE.'/documents', [
            'document_type' => $type->value,
            'file' => UploadedFile::fake()->create('doc.pdf', 200, 'application/pdf'),
        ]);
    }

    /** @param array<int, SellerDocumentType> $types */
    private function uploadAll(array $types): void
    {
        foreach ($types as $type) {
            $this->upload($type)->assertCreated();
        }
    }

    // ==================================================================
    // What each country is asked for
    // ==================================================================

    public function test_a_pakistani_seller_is_asked_for_a_cnic(): void
    {
        $this->customer();

        $response = $this->apply('PK');

        $response->assertCreated();
        $response->assertJsonPath('data.country', 'PK');

        $identity = $response->json('data.required_documents.identity');
        // One option, needing both sides.
        $this->assertCount(1, $identity);
        $this->assertSame(['cnic_front', 'cnic_back'], array_column($identity[0], 'type'));
    }

    /** P10-3 — the UK has two ways to prove who you are. */
    public function test_a_uk_seller_may_use_a_passport_or_a_driving_licence(): void
    {
        $this->customer();

        $response = $this->apply('GB');

        $response->assertCreated();
        $identity = $response->json('data.required_documents.identity');

        $this->assertCount(2, $identity);
        $this->assertSame(['passport'], array_column($identity[0], 'type'));
        $this->assertSame(['driving_licence_front', 'driving_licence_back'], array_column($identity[1], 'type'));
    }

    /** P10-4 — a country we have no rules for still works. */
    public function test_an_unlisted_country_falls_back_to_a_passport(): void
    {
        $this->customer();

        $response = $this->apply('JP');

        $response->assertCreated();
        $response->assertJsonPath('data.country', 'JP');
        $this->assertSame(
            ['passport'],
            array_column($response->json('data.required_documents.identity')[0], 'type'),
        );
    }

    public function test_the_country_is_required_and_must_be_a_code(): void
    {
        $this->customer();

        $this->postJson(self::BASE, [
            'store_name' => 'No Country',
            'business_name' => 'No Country Ltd',
            'business_type' => 'company',
            'accept_document_processing' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('country');

        $this->apply('Pakistan')->assertStatus(422)->assertJsonValidationErrors('country');
    }

    // ==================================================================
    // Uploading
    // ==================================================================

    public function test_a_uk_seller_cannot_upload_a_cnic(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('GB')->assertCreated();

        // Not a rejection of their document — a mistake on our side if we
        // ever asked for it.
        $this->upload(SellerDocumentType::CNIC_FRONT)->assertStatus(422);

        $this->assertDatabaseCount('seller_application_documents', 0);
    }

    public function test_a_pakistani_seller_cannot_upload_a_passport(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('PK')->assertCreated();

        $this->upload(SellerDocumentType::PASSPORT)->assertStatus(422);
    }

    // ==================================================================
    // Submitting
    // ==================================================================

    /** P10-3 — one complete option satisfies the group. */
    public function test_a_uk_seller_submits_with_a_passport_alone(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('GB')->assertCreated();

        $this->uploadAll([
            SellerDocumentType::PASSPORT,
            SellerDocumentType::BUSINESS_LICENSE,
            SellerDocumentType::TAX_CERTIFICATE,
            SellerDocumentType::BANK_STATEMENT,
        ]);

        $this->postJson(self::BASE.'/submit')->assertOk();

        $this->assertSame(
            SellerApplicationStatus::PENDING,
            SellerApplication::query()->firstOrFail()->status,
        );
    }

    public function test_a_uk_seller_submits_with_both_sides_of_a_driving_licence(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('GB')->assertCreated();

        $this->uploadAll([
            SellerDocumentType::DRIVING_LICENCE_FRONT,
            SellerDocumentType::DRIVING_LICENCE_BACK,
            SellerDocumentType::BUSINESS_LICENSE,
            SellerDocumentType::TAX_CERTIFICATE,
            SellerDocumentType::BANK_STATEMENT,
        ]);

        $this->postJson(self::BASE.'/submit')->assertOk();
    }

    /**
     * Somebody who uploaded one side of a licence is told to finish THAT
     * option, not to go and find a passport as well.
     */
    public function test_a_half_finished_option_asks_for_its_own_missing_half(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('GB')->assertCreated();

        $this->uploadAll([
            SellerDocumentType::DRIVING_LICENCE_FRONT,
            SellerDocumentType::BUSINESS_LICENSE,
            SellerDocumentType::TAX_CERTIFICATE,
            SellerDocumentType::BANK_STATEMENT,
        ]);

        $response = $this->postJson(self::BASE.'/submit');

        $response->assertStatus(422);
        $this->assertSame(
            ['driving_licence_back'],
            $response->json('errors.missing_documents') ?? $response->json('missing_documents'),
        );
    }

    public function test_a_pakistani_seller_still_needs_both_cnic_sides(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('PK')->assertCreated();

        $this->uploadAll([
            SellerDocumentType::CNIC_FRONT,
            SellerDocumentType::BUSINESS_LICENSE,
            SellerDocumentType::TAX_CERTIFICATE,
            SellerDocumentType::BANK_STATEMENT,
        ]);

        $this->postJson(self::BASE.'/submit')->assertStatus(422);
    }

    // ==================================================================
    // C54 — Layer 2 must still run outside Pakistan
    // ==================================================================

    /**
     * The trap: crossCheck() used to loop over EVERY document type and
     * bail on the first one missing. A UK application has no CNIC, so
     * Layer 2 would have switched itself off for every seller on earth.
     */
    public function test_layer_two_still_runs_for_a_uk_application(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('GB')->assertCreated();

        $this->uploadAll([
            SellerDocumentType::PASSPORT,
            SellerDocumentType::BUSINESS_LICENSE,
            SellerDocumentType::TAX_CERTIFICATE,
            SellerDocumentType::BANK_STATEMENT,
        ]);

        $this->postJson(self::BASE.'/submit')->assertOk();

        $application = SellerApplication::query()->firstOrFail();

        // 'unknown' would mean the cross-checks were skipped.
        $this->assertNotNull($application->ai_risk_level);
        $this->assertNotSame('unknown', $application->ai_risk_level?->value);

        $keys = array_column($application->ai_report['checks'] ?? [], 'key');
        $this->assertContains('identity_not_expired', $keys);
        $this->assertContains('identity_name_matches_account', $keys);

        // C53 — a passport has one side, so there is nothing to compare.
        $this->assertNotContains('identity_numbers_match', $keys);
    }

    public function test_both_sides_are_compared_when_the_document_has_two(): void
    {
        $this->fakeDocumentVerifier();
        $this->customer();
        $this->apply('PK')->assertCreated();

        $this->uploadAll([
            SellerDocumentType::CNIC_FRONT,
            SellerDocumentType::CNIC_BACK,
            SellerDocumentType::BUSINESS_LICENSE,
            SellerDocumentType::TAX_CERTIFICATE,
            SellerDocumentType::BANK_STATEMENT,
        ]);

        $this->postJson(self::BASE.'/submit')->assertOk();

        $keys = array_column(
            SellerApplication::query()->firstOrFail()->ai_report['checks'] ?? [],
            'key',
        );

        $this->assertContains('identity_numbers_match', $keys);
        // C52 — Pakistan's 13-digit rule is checked here now, not inside
        // the verifier where it used to shred passport numbers.
        $this->assertContains('identity_number_format', $keys);
    }
}
