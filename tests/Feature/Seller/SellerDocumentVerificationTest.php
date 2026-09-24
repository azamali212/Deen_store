<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\DTO\DocumentVerificationResultDTO;
use App\Domain\Seller\Enums\DocumentRejectionCategory;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Exceptions\DocumentVerificationUnavailableException;
use App\Domain\Seller\Notifications\SellerBankUnverifiedNotification;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeDocumentVerifier;
use Tests\Support\FakesDocumentVerification;
use Tests\TestCase;

/**
 * Phase 5 — AI document verification (BLUEPRINT.txt section 10).
 */
final class SellerDocumentVerificationTest extends TestCase
{
    use RefreshDatabase;
    use FakesDocumentVerification;

    private const CUSTOMER = '/api/v1/seller/application';

    private const ADMIN = '/api/v1/admin/seller-applications';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    private function customer(string $name = 'Azam Ali'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->assignRole('customer');

        return $user;
    }

    private function draftFor(User $user, string $businessName = 'Zimal Fabrics Pvt Ltd'): SellerApplication
    {
        return SellerApplication::factory()->create([
            'user_id' => $user->id,
            'business_name' => $businessName,
        ]);
    }

    /**
     * All 5 documents already read by the AI — consistent by default.
     *
     * @param  array<string, array<string, string|null>>  $fieldOverrides  keyed by document type
     * @param  array<string, array<int, string>>  $concerns  keyed by document type
     */
    private function giveAiCheckedDocuments(
        SellerApplication $application,
        FakeDocumentVerifier $fake,
        array $fieldOverrides = [],
        array $concerns = [],
    ): void {
        foreach (SellerDocumentType::cases() as $type) {
            SellerApplicationDocument::factory()
                ->ofType($type)
                ->aiChecked(
                    array_merge($fake->fieldsFor($type), $fieldOverrides[$type->value] ?? []),
                    $concerns[$type->value] ?? [],
                )
                ->create(['seller_application_id' => $application->id]);
        }
    }

    private function upload(SellerDocumentType $type)
    {
        return $this->postJson(self::CUSTOMER.'/documents', [
            'document_type' => $type->value,
            'file' => UploadedFile::fake()->image($type->value.'.jpg'),
        ]);
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');
        Sanctum::actingAs($admin);

        return $admin;
    }

    // ==================================================================
    // Layer 1 — at upload
    // ==================================================================

    public function test_an_explicit_image_is_blocked_and_never_stored(): void
    {
        $this->fakeDocumentVerifier()->returnFor(
            SellerDocumentType::CNIC_FRONT,
            DocumentVerificationResultDTO::rejected(DocumentRejectionCategory::EXPLICIT),
        );
        $user = $this->customer();
        $this->draftFor($user);
        Sanctum::actingAs($user);

        $response = $this->upload(SellerDocumentType::CNIC_FRONT);

        $response->assertStatus(422);
        $response->assertJsonPath('reason', 'explicit');
        $response->assertJsonPath('message', 'This file was rejected because it contains inappropriate content.');

        $this->assertDatabaseCount('seller_application_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.document.rejected_by_ai',
            'severity' => 'warning',
            'status' => 'denied',
            'subject_id' => $user->id,
        ]);
    }

    public function test_the_wrong_document_is_blocked_with_our_own_clear_message(): void
    {
        $this->fakeDocumentVerifier()->returnFor(
            SellerDocumentType::CNIC_FRONT,
            DocumentVerificationResultDTO::rejected(DocumentRejectionCategory::WRONG_DOCUMENT),
        );
        $user = $this->customer();
        $this->draftFor($user);
        Sanctum::actingAs($user);

        $response = $this->upload(SellerDocumentType::CNIC_FRONT);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'This does not look like a CNIC (Front). Please upload the correct document.');
        $this->assertDatabaseCount('seller_application_documents', 0);
    }

    public function test_an_accepted_document_stores_what_the_ai_read_encrypted(): void
    {
        $this->fakeDocumentVerifier();
        $user = $this->customer();
        $this->draftFor($user);
        Sanctum::actingAs($user);

        $this->upload(SellerDocumentType::CNIC_FRONT)->assertCreated();

        $document = SellerApplicationDocument::query()->firstOrFail();
        $this->assertSame('passed', $document->ai_status->value);
        $this->assertSame('3520212345671', $document->aiFields()['cnic_number']);

        // Personal data read off the CNIC is ciphertext in the database.
        $raw = (string) DB::table('seller_application_documents')->value('ai_findings');
        $this->assertStringNotContainsString('3520212345671', $raw);
        $this->assertStringNotContainsString('Azam', $raw);
    }

    public function test_a_document_with_possible_editing_is_accepted_but_flagged(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $fake->returnFor(
            SellerDocumentType::BANK_STATEMENT,
            DocumentVerificationResultDTO::accepted(
                $fake->fieldsFor(SellerDocumentType::BANK_STATEMENT),
                ['Balance digits look re-typed'],
            ),
        );
        $user = $this->customer();
        $this->draftFor($user);
        Sanctum::actingAs($user);

        $this->upload(SellerDocumentType::BANK_STATEMENT)->assertCreated();

        $this->assertSame('flagged', SellerApplicationDocument::query()->firstOrFail()->ai_status->value);
    }

    public function test_an_ai_outage_returns_503_and_stores_nothing(): void
    {
        $this->fakeDocumentVerifier()->failWith(
            DocumentVerificationUnavailableException::providerError('HTTP 500'),
        );
        $user = $this->customer();
        $this->draftFor($user);
        Sanctum::actingAs($user);

        $response = $this->upload(SellerDocumentType::CNIC_FRONT);

        $response->assertStatus(503);
        $this->assertStringNotContainsString('HTTP 500', $response->getContent());
        $this->assertDatabaseCount('seller_application_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_with_ai_switched_off_uploads_still_work_as_skipped(): void
    {
        // No fake bound — the real binding with the switch OFF (default)
        // resolves to NullDocumentVerifier. Nothing leaves the server.
        config(['services.gemini.document_verification.enabled' => false]);
        $user = $this->customer();
        $this->draftFor($user);
        Sanctum::actingAs($user);

        $this->upload(SellerDocumentType::CNIC_FRONT)->assertCreated();

        $this->assertSame('skipped', SellerApplicationDocument::query()->firstOrFail()->ai_status->value);
    }

    public function test_a_locked_application_never_costs_an_ai_call(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer();
        SellerApplication::factory()->pending()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->upload(SellerDocumentType::CNIC_FRONT)->assertStatus(409);

        $this->assertSame([], $fake->verifiedTypes);
    }

    // ==================================================================
    // Layer 2 — at submit
    // ==================================================================

    public function test_submit_is_blocked_when_cnic_front_and_back_numbers_differ(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer();
        $application = $this->draftFor($user);
        $this->giveAiCheckedDocuments($application, $fake, [
            'cnic_back' => ['cnic_number' => '4210199999991'],
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson(self::CUSTOMER.'/submit');

        $response->assertStatus(422);
        $response->assertJsonCount(1, 'failed_checks');
        $response->assertJsonPath(
            'failed_checks.0',
            'The CNIC numbers on the front and back sides do not match. Please upload both sides of the SAME CNIC.',
        );
        // The customer-facing message never contains the numbers themselves.
        $this->assertStringNotContainsString('4210199999991', $response->getContent());

        $this->assertSame('draft', $application->fresh()->status->value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.application.blocked_by_ai']);
    }

    public function test_submit_is_blocked_when_the_cnic_has_expired(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer();
        $application = $this->draftFor($user);
        $this->giveAiCheckedDocuments($application, $fake, [
            'cnic_front' => ['date_of_expiry' => '2020-01-01'],
        ]);
        Sanctum::actingAs($user);

        $this->postJson(self::CUSTOMER.'/submit')
            ->assertStatus(422)
            ->assertJsonPath('failed_checks.0', 'Your CNIC has expired. Please upload a valid CNIC.');
    }

    public function test_a_clean_consistent_application_goes_to_the_admin_as_low_risk(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer();
        $application = $this->draftFor($user);
        $this->giveAiCheckedDocuments($application, $fake);
        Sanctum::actingAs($user);

        $this->postJson(self::CUSTOMER.'/submit')->assertOk();

        $fresh = $application->fresh();
        $this->assertSame('pending', $fresh->status->value);
        $this->assertSame('low', $fresh->ai_risk_level->value);
        $this->assertSame(0, $fresh->ai_report['summary']['warnings']);
        $this->assertSame(0, $fresh->ai_report['summary']['failed']);
    }

    public function test_a_name_mismatch_is_only_flagged_for_the_admin(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer('Azam Ali');
        $application = $this->draftFor($user);
        $this->giveAiCheckedDocuments($application, $fake, [
            'cnic_front' => ['full_name' => 'Bilal Khan'],
        ]);
        Sanctum::actingAs($user);

        // NOT blocked — goes to the admin with a warning.
        $this->postJson(self::CUSTOMER.'/submit')->assertOk();

        $fresh = $application->fresh();
        $this->assertSame('pending', $fresh->status->value);
        $this->assertSame('medium', $fresh->ai_risk_level->value);

        $nameCheck = collect($fresh->ai_report['checks'])->firstWhere('key', 'cnic_name_matches_account');
        $this->assertSame('warn', $nameCheck['result']);
    }

    public function test_possible_tampering_makes_the_application_high_risk_but_does_not_block_it(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer();
        $application = $this->draftFor($user);
        $this->giveAiCheckedDocuments($application, $fake, [], [
            'cnic_front' => ['Photo area looks pasted over'],
        ]);
        Sanctum::actingAs($user);

        $this->postJson(self::CUSTOMER.'/submit')->assertOk();

        $this->assertSame('high', $application->fresh()->ai_risk_level->value);
    }

    public function test_documents_not_read_by_ai_give_an_unknown_risk_and_do_not_block(): void
    {
        $user = $this->customer();
        $application = $this->draftFor($user);
        foreach (SellerDocumentType::cases() as $type) {
            SellerApplicationDocument::factory()->ofType($type)->aiSkipped()
                ->create(['seller_application_id' => $application->id]);
        }
        Sanctum::actingAs($user);

        $this->postJson(self::CUSTOMER.'/submit')->assertOk();

        $fresh = $application->fresh();
        $this->assertSame('unknown', $fresh->ai_risk_level->value);
        $this->assertSame('skipped', $fresh->ai_report['status']);
    }

    // ==================================================================
    // Layer 3 — the admin sees the report, the customer doesn't
    // ==================================================================

    public function test_the_admin_sees_the_ai_report_and_what_the_ai_read(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $user = $this->customer();
        $application = $this->draftFor($user);
        $this->giveAiCheckedDocuments($application, $fake);
        Sanctum::actingAs($user);
        $this->postJson(self::CUSTOMER.'/submit')->assertOk();

        // Customer view: no AI internals.
        $customerView = $this->getJson(self::CUSTOMER);
        $customerView->assertJsonMissingPath('data.ai_verification');
        $customerView->assertJsonMissingPath('data.documents.0.ai');

        // Admin view: full report + extracted fields.
        $this->actingAsAdmin();
        $adminView = $this->getJson(self::ADMIN.'/'.$application->id);

        $adminView->assertOk();
        $adminView->assertJsonPath('data.ai_verification.risk_level', 'low');
        $adminView->assertJsonPath('data.ai_verification.report.status', 'completed');

        $cnicFront = collect($adminView->json('data.documents'))->firstWhere('document_type', 'cnic_front');
        $this->assertSame('passed', $cnicFront['ai']['status']);
        $this->assertSame('3520212345671', $cnicFront['ai']['extracted']['cnic_number']);
    }

    public function test_the_admin_can_list_high_risk_applications_first(): void
    {
        SellerApplication::factory()->pending()->create(['ai_risk_level' => 'high']);
        SellerApplication::factory()->pending()->create(['ai_risk_level' => 'low']);
        $this->actingAsAdmin();

        $response = $this->getJson(self::ADMIN.'?risk=high');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.ai_verification.risk_level', 'high');
    }

    // ==================================================================
    // P5-2 — payout bank vs the verified bank statement
    // ==================================================================

    /**
     * An approved seller whose onboarding bank statement the AI read as
     * "Zimal Fabrics Pvt Ltd", account ending 1234.
     */
    private function sellerWithVerifiedStatement(): SellerProfile
    {
        $fake = new FakeDocumentVerifier;
        $user = $this->customer();
        $user->assignRole('seller');

        $application = SellerApplication::factory()->approved()->create([
            'user_id' => $user->id,
            'business_name' => 'Zimal Fabrics Pvt Ltd',
        ]);

        SellerApplicationDocument::factory()
            ->ofType(SellerDocumentType::BANK_STATEMENT)
            ->aiChecked($fake->fieldsFor(SellerDocumentType::BANK_STATEMENT))
            ->create(['seller_application_id' => $application->id]);

        $profile = SellerProfile::factory()->create([
            'user_id' => $user->id,
            'seller_application_id' => $application->id,
            'bank_account_title' => null,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_last4' => null,
        ]);

        Sanctum::actingAs($user);

        return $profile;
    }

    public function test_a_payout_bank_matching_the_statement_is_marked_matched(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');
        $profile = $this->sellerWithVerifiedStatement();

        $response = $this->putJson('/api/v1/seller/profile', [
            'bank_account_title' => 'Zimal Fabrics',
            'bank_name' => 'Meezan Bank',
            'bank_account_number' => '01234567891234',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.bank.verification_status', 'matched');
        Notification::assertNotSentTo($admin, SellerBankUnverifiedNotification::class);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'seller.bank.unverified']);
        $this->assertSame('matched', $profile->fresh()->bank_verification_status->value);
    }

    public function test_a_payout_bank_not_matching_the_statement_is_saved_but_flagged(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');
        $profile = $this->sellerWithVerifiedStatement();

        $response = $this->putJson('/api/v1/seller/profile', [
            'bank_account_title' => 'Someone Else',
            'bank_name' => 'HBL',
            'bank_account_number' => '99998888777799',
        ]);

        // Decided: allowed + flagged, not refused.
        $response->assertOk();
        $response->assertJsonPath('data.bank.verification_status', 'mismatch');
        $this->assertSame('7799', $profile->fresh()->bank_account_last4);

        Notification::assertSentTo($admin, SellerBankUnverifiedNotification::class);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.bank.unverified',
            'severity' => 'warning',
            'subject_id' => $profile->user_id,
        ]);
    }

    public function test_without_an_ai_read_statement_the_bank_status_is_unknown(): void
    {
        Notification::fake();
        $user = $this->customer();
        $user->assignRole('seller');
        $profile = SellerProfile::factory()->create(['user_id' => $user->id]); // no AI-read statement
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/seller/profile', [
            'bank_account_title' => 'Zimal Fabrics',
            'bank_name' => 'Meezan Bank',
            'bank_account_number' => '01234567895555',
        ])->assertOk()->assertJsonPath('data.bank.verification_status', 'unknown');

        $this->assertDatabaseMissing('audit_logs', ['action' => 'seller.bank.unverified']);
    }
}
