<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\DTO\DocumentVerificationResultDTO;
use App\Domain\Seller\Enums\DocumentRejectionCategory;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Enums\SellerRenewalStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Notifications\SellerDocumentRenewalReviewedNotification;
use App\Domain\Seller\Notifications\SellerDocumentRenewalSubmittedNotification;
use App\Models\SellerDocumentRenewal;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakeDocumentVerifier;
use Tests\Support\FakesDocumentVerification;
use Tests\TestCase;

/**
 * Phase 8a — re-KYC (BLUEPRINT section 13a).
 */
final class SellerDocumentRenewalTest extends TestCase
{
    use RefreshDatabase;
    use FakesDocumentVerification;

    private const RENEWALS = '/api/v1/seller/documents';

    private const ADMIN = '/api/v1/admin/seller-renewals';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        Notification::fake();
    }

    private function store(array $overrides = []): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        return SellerProfile::factory()->create(['user_id' => $owner->id] + $overrides);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        return $admin;
    }

    private function upload(SellerDocumentType $type = SellerDocumentType::CNIC_FRONT)
    {
        return $this->postJson(self::RENEWALS, [
            'document_type' => $type->value,
            'file' => UploadedFile::fake()->create('new-cnic.pdf', 200, 'application/pdf'),
        ]);
    }

    // ==================================================================
    // Uploading
    // ==================================================================

    public function test_the_owner_can_upload_a_replacement_and_it_waits_for_an_admin(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $reviewer = $this->admin();
        $store = $this->store(['cnic_expires_at' => now()->addDays(5)->toDateString()]);
        Sanctum::actingAs($store->user);

        $response = $this->upload();

        $response->assertCreated();
        $response->assertJsonPath('data.status', SellerRenewalStatus::PENDING->value);
        // P8-4 — AI reads it, an admin still confirms it.
        $this->assertContains(SellerDocumentType::CNIC_FRONT->value, $fake->verifiedTypes);
        // Never the private path (D7).
        $response->assertJsonMissingPath('data.file_path');

        $this->assertDatabaseCount('seller_document_renewals', 1);
        Notification::assertSentTo($reviewer, SellerDocumentRenewalSubmittedNotification::class);
    }

    /** P8-1 — the date is lifted out; the identity number is not. */
    public function test_the_expiry_date_is_extracted_into_a_plain_column_and_the_rest_stays_encrypted(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->upload()->assertCreated();

        $renewal = SellerDocumentRenewal::query()->firstOrFail();
        $this->assertNotNull($renewal->extracted_expiry_date);

        // The CNIC number the AI read is ciphertext on disk.
        $raw = (string) \Illuminate\Support\Facades\DB::table('seller_document_renewals')
            ->where('id', $renewal->id)->value('ai_findings');
        $this->assertStringNotContainsString('3520212345671', $raw);
        $this->assertSame('3520212345671', $renewal->aiFields()['cnic_number']);
    }

    public function test_a_rejected_document_is_never_stored(): void
    {
        $this->fakeDocumentVerifier()->returnFor(
            SellerDocumentType::CNIC_FRONT,
            DocumentVerificationResultDTO::rejected(DocumentRejectionCategory::WRONG_DOCUMENT),
        );
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->upload()->assertStatus(422);

        $this->assertDatabaseCount('seller_document_renewals', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_only_the_owner_may_upload_identity_documents(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();

        $manager = User::factory()->create();
        $manager->assignRole('customer');
        $manager->assignRole('seller_manager');
        SellerTeamMember::factory()->create([
            'seller_profile_id' => $store->id,
            'user_id' => $manager->id,
            'role' => SellerTeamRole::MANAGER->value,
        ]);

        Sanctum::actingAs($manager);

        // A manager runs the shop; the owner's CNIC is not theirs to replace.
        $this->upload()->assertStatus(403);
        $this->assertDatabaseCount('seller_document_renewals', 0);
    }

    public function test_a_suspended_store_cannot_upload(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store([
            'status' => \App\Domain\Seller\Enums\SellerProfileStatus::SUSPENDED->value,
            'suspended_at' => now(),
            'suspension_reason' => 'Under investigation.',
        ]);
        Sanctum::actingAs($store->user);

        $this->upload()->assertStatus(403);
    }

    public function test_a_document_type_that_never_expires_is_refused(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->postJson(self::RENEWALS, [
            'document_type' => SellerDocumentType::TAX_CERTIFICATE->value,
            'file' => UploadedFile::fake()->create('tax.pdf', 100, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('document_type');
    }

    public function test_a_second_upload_of_the_same_type_while_one_is_pending_is_refused(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->upload()->assertCreated();
        $this->upload()->assertStatus(409);

        $this->assertDatabaseCount('seller_document_renewals', 1);
    }

    // ==================================================================
    // Admin review
    // ==================================================================

    public function test_a_customer_cannot_reach_the_admin_queue(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->getJson(self::ADMIN)->assertStatus(403);
    }

    public function test_the_queue_shows_what_the_ai_read_and_whether_money_is_held(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store([
            'cnic_expires_at' => now()->subDay()->toDateString(),
            'kyc_status' => SellerKycStatus::EXPIRED->value,
        ]);
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();

        Sanctum::actingAs($this->admin());

        $response = $this->getJson(self::ADMIN);

        $response->assertOk();
        $response->assertJsonPath('data.0.store.payouts_on_hold', true);
        $response->assertJsonPath('data.0.ai_fields.cnic_number', '3520212345671');
        $response->assertJsonMissingPath('data.0.file_path');
    }

    public function test_approving_updates_the_expiry_and_releases_payouts(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store([
            'cnic_expires_at' => now()->subDay()->toDateString(),
            'kyc_status' => SellerKycStatus::EXPIRED->value,
            'bank_account_last4' => '1234',
            'bank_verification_status' => \App\Domain\Seller\Enums\BankVerificationStatus::ADMIN_VERIFIED->value,
        ]);
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();
        $renewalId = SellerDocumentRenewal::query()->value('id');

        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$renewalId}/review", ['decision' => 'approve'])
            ->assertOk()
            ->assertJsonPath('data.status', SellerRenewalStatus::APPROVED->value);

        $store->refresh();
        // The AI's date (5 years out) replaced yesterday's.
        $this->assertTrue($store->cnic_expires_at->isFuture());
        $this->assertSame(SellerKycStatus::VALID, $store->kycStatus());
        $this->assertTrue($store->isPayoutReady());
        // C29 — the next expiry cycle must be able to warn again.
        $this->assertNull($store->kyc_notified_at);

        Notification::assertSentTo($store->user, SellerDocumentRenewalReviewedNotification::class);
    }

    public function test_rejecting_needs_a_reason_and_lets_the_seller_try_again(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();
        $renewalId = SellerDocumentRenewal::query()->value('id');

        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $this->postJson(self::ADMIN."/{$renewalId}/review", ['decision' => 'reject'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->postJson(self::ADMIN."/{$renewalId}/review", [
            'decision' => 'reject',
            'reason' => 'The photo is cut off at the edges.',
        ])->assertOk();

        $this->assertDatabaseHas('seller_document_renewals', [
            'id' => $renewalId,
            'status' => SellerRenewalStatus::REJECTED->value,
            'rejection_reason' => 'The photo is cut off at the edges.',
        ]);

        // Nothing pending any more, so a fresh attempt is allowed.
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();
    }

    public function test_the_same_renewal_cannot_be_reviewed_twice(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();
        $renewalId = SellerDocumentRenewal::query()->value('id');

        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$renewalId}/review", ['decision' => 'approve'])->assertOk();
        $this->postJson(self::ADMIN."/{$renewalId}/review", ['decision' => 'approve'])->assertStatus(409);
    }

    /** C12 — segregation of duties: an admin may also run a store. */
    public function test_an_admin_cannot_review_their_own_stores_documents(): void
    {
        $this->fakeDocumentVerifier();
        $admin = $this->admin();
        $admin->assignRole('customer');
        $admin->assignRole('seller');
        $store = SellerProfile::factory()->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);
        $this->upload()->assertCreated();
        $renewalId = SellerDocumentRenewal::query()->value('id');

        $this->postJson(self::ADMIN."/{$renewalId}/review", ['decision' => 'approve'])->assertStatus(403);
        $this->getJson(self::ADMIN."/{$renewalId}/file")->assertStatus(403);
    }

    public function test_an_unknown_renewal_is_404(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN.'/9999/review', ['decision' => 'approve'])->assertStatus(404);
    }

    public function test_the_admin_can_download_the_uploaded_file(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();
        $renewalId = SellerDocumentRenewal::query()->value('id');

        Sanctum::actingAs($this->admin());

        $response = $this->get(self::ADMIN."/{$renewalId}/file", ['Accept' => 'application/json']);

        $response->assertOk();
        // C14 — our own name, never the seller's uploaded file name.
        $response->assertDownload('renewal-'.$renewalId.'-cnic_front.pdf');
    }

    public function test_the_seller_sees_their_own_renewals_without_the_ai_extract(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->upload()->assertCreated();

        $response = $this->getJson(self::RENEWALS);

        $response->assertOk();
        $response->assertJsonPath('data.0.document_type', 'cnic_front');
        $response->assertJsonMissingPath('data.0.ai_fields');
        $response->assertJsonMissingPath('data.0.file_path');
    }

    /**
     * C35 — the throttle sits ahead of the controller, so a blocked
     * request never reaches the billable AI call.
     */
    public function test_uploads_are_rate_limited_and_a_blocked_request_never_calls_the_ai(): void
    {
        $fake = $this->fakeDocumentVerifier();
        $store = $this->store();
        Sanctum::actingAs($store->user);

        // 20 an hour. The first is created; the rest are 409s (one pending
        // per type) but they still cost a request — and would still have
        // cost an AI call without the check order we use.
        for ($i = 0; $i < 20; $i++) {
            $this->upload();
        }

        $callsBefore = count($fake->verifiedTypes);

        $this->upload()->assertStatus(429);

        $this->assertSame(
            $callsBefore,
            count($fake->verifiedTypes),
            'A throttled request must never reach the AI verifier.',
        );
    }
}
