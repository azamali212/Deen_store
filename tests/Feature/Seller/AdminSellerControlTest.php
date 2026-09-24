<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Notifications\SellerBankProofPendingNotification;
use App\Domain\Seller\Notifications\SellerBankProofReviewedNotification;
use App\Domain\Seller\Notifications\SellerStoreReactivatedNotification;
use App\Domain\Seller\Notifications\SellerStoreSuspendedNotification;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesDocumentVerification;
use Tests\TestCase;

/**
 * Phase 6 — admin control of live stores + the way out of a bank mismatch.
 */
final class AdminSellerControlTest extends TestCase
{
    use RefreshDatabase;
    use FakesDocumentVerification;

    private const ADMIN = '/api/v1/admin/sellers';

    private const SELLER = '/api/v1/seller/profile';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function actingAsAdmin(string $role = 'platform_admin'): User
    {
        $admin = User::factory()->create();
        $admin->assignRole($role);
        Sanctum::actingAs($admin);

        return $admin;
    }

    private function store(array $overrides = []): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        return SellerProfile::factory()->create(['user_id' => $owner->id] + $overrides);
    }

    private function actingAsSellerOf(SellerProfile $profile): void
    {
        Sanctum::actingAs($profile->user);
    }

    // ==================================================================
    // Access + list
    // ==================================================================

    public function test_a_customer_cannot_use_the_admin_store_endpoints(): void
    {
        $profile = $this->store();
        $this->actingAsSellerOf($profile);

        $this->getJson(self::ADMIN)->assertStatus(403);
    }

    public function test_admin_can_list_live_stores_and_filter_them(): void
    {
        $this->store(['store_name' => 'Active Store']);
        $this->store(['store_name' => 'Bad Store'])->update([
            'status' => 'suspended',
            'suspension_reason' => 'Fraud',
            'suspended_at' => now(),
        ]);
        $this->store([
            'store_name' => 'Mismatch Store',
            'bank_verification_status' => BankVerificationStatus::MISMATCH,
        ]);
        $this->actingAsAdmin();

        $this->getJson(self::ADMIN)->assertOk()->assertJsonCount(3, 'data');
        $this->getJson(self::ADMIN.'?status=suspended')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.store_name', 'Bad Store');

        // The whole point of the filter: find the stuck mismatches.
        $this->getJson(self::ADMIN.'?bank=mismatch')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.store_name', 'Mismatch Store');
    }

    public function test_store_detail_never_exposes_the_bank_number_or_proof_path(): void
    {
        $profile = $this->store([
            'bank_account_number' => '01234567891234',
            'bank_account_last4' => '1234',
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson(self::ADMIN.'/'.$profile->id);

        $response->assertOk();
        $response->assertJsonPath('data.bank.account_number_masked', '****1234');
        $response->assertJsonPath('data.owner.email', $profile->user->email);
        $this->assertStringNotContainsString('01234567891234', $response->getContent());
        $response->assertJsonMissingPath('data.bank.bank_proof_path');
    }

    public function test_an_unknown_store_is_404(): void
    {
        $this->actingAsAdmin();

        $this->getJson(self::ADMIN.'/999999')->assertStatus(404);
        $this->postJson(self::ADMIN.'/999999/suspend', ['reason' => 'Some reason'])->assertStatus(404);
    }

    // ==================================================================
    // P6-1 — suspend / reactivate
    // ==================================================================

    public function test_suspending_closes_the_store_and_emails_the_seller(): void
    {
        Notification::fake();
        $profile = $this->store();
        $admin = $this->actingAsAdmin();

        $response = $this->postJson(self::ADMIN."/{$profile->id}/suspend", [
            'reason' => 'Selling counterfeit products.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'suspended');
        $response->assertJsonPath('data.suspension.reason', 'Selling counterfeit products.');

        $fresh = $profile->fresh();
        $this->assertSame($admin->id, $fresh->suspended_by);
        $this->assertNotNull($fresh->suspended_at);

        Notification::assertSentTo(
            $profile->user,
            SellerStoreSuspendedNotification::class,
            fn (SellerStoreSuspendedNotification $n): bool => $n->reason === 'Selling counterfeit products.',
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.suspended',
            'severity' => 'warning',
            'subject_id' => $profile->user_id,
        ]);
    }

    public function test_suspending_without_a_reason_is_refused(): void
    {
        $profile = $this->store();
        $this->actingAsAdmin();

        $this->postJson(self::ADMIN."/{$profile->id}/suspend", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertSame('active', $profile->fresh()->status->value);
    }

    public function test_a_suspended_store_cannot_be_suspended_again(): void
    {
        $profile = $this->store();
        $profile->update(['status' => 'suspended', 'suspended_at' => now()]);
        $this->actingAsAdmin();

        $this->postJson(self::ADMIN."/{$profile->id}/suspend", ['reason' => 'Again for some reason'])
            ->assertStatus(409);
    }

    public function test_an_admin_cannot_suspend_their_own_store(): void
    {
        $admin = $this->actingAsAdmin('super_admin');
        $profile = SellerProfile::factory()->create(['user_id' => $admin->id]);

        $this->postJson(self::ADMIN."/{$profile->id}/suspend", ['reason' => 'Trying it on myself'])
            ->assertStatus(403);

        $this->assertSame('active', $profile->fresh()->status->value);
    }

    public function test_a_suspended_seller_can_still_view_but_not_change_their_store(): void
    {
        $this->fakeDocumentVerifier();
        $profile = $this->store();
        $profile->update([
            'status' => 'suspended',
            'suspension_reason' => 'Under investigation.',
            'suspended_at' => now(),
        ]);
        $this->actingAsSellerOf($profile);

        // Read: allowed, with the reason shown.
        $this->getJson(self::SELLER)
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended')
            ->assertJsonPath('data.suspension.reason', 'Under investigation.');

        // Write: refused, everywhere.
        $this->putJson(self::SELLER, ['description' => 'New text'])->assertStatus(403);
        $this->post(self::SELLER.'/logo', ['logo' => UploadedFile::fake()->image('l.png')], ['Accept' => 'application/json'])
            ->assertStatus(403);
        $this->deleteJson(self::SELLER.'/logo')->assertStatus(403);
    }

    public function test_reactivating_reopens_the_store_and_emails_the_seller(): void
    {
        Notification::fake();
        $profile = $this->store();
        $profile->update([
            'status' => 'suspended',
            'suspension_reason' => 'Old reason',
            'suspended_at' => now(),
        ]);
        $this->actingAsAdmin();

        $response = $this->postJson(self::ADMIN."/{$profile->id}/reactivate");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'active');

        $fresh = $profile->fresh();
        $this->assertNull($fresh->suspension_reason);
        $this->assertNull($fresh->suspended_at);

        Notification::assertSentTo($profile->user, SellerStoreReactivatedNotification::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.reactivated']);
    }

    public function test_an_active_store_cannot_be_reactivated(): void
    {
        $profile = $this->store();
        $this->actingAsAdmin();

        $this->postJson(self::ADMIN."/{$profile->id}/reactivate")->assertStatus(409);
    }

    // ==================================================================
    // P6-2 — bank proof: upload
    // ==================================================================

    public function test_a_matching_statement_verifies_the_payout_account_without_an_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        // The fake AI reads "Zimal Fabrics Pvt Ltd" / last4 1234.
        $this->fakeDocumentVerifier();
        $profile = $this->store([
            'bank_account_title' => 'Zimal Fabrics Pvt Ltd',
            'bank_account_number' => '01234567891234',
            'bank_account_last4' => '1234',
            'bank_verification_status' => BankVerificationStatus::MISMATCH,
        ]);
        $this->actingAsSellerOf($profile);

        $response = $this->post(self::SELLER.'/bank/statement', [
            'file' => UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonPath('data.bank.verification_status', 'matched');
        $response->assertJsonPath('data.bank.payout_ready', true);

        // Nothing for an admin to do.
        Notification::assertNotSentTo($admin, SellerBankProofPendingNotification::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.bank.proof_uploaded']);
    }

    public function test_a_non_matching_statement_waits_for_an_admin(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        $this->fakeDocumentVerifier();
        $profile = $this->store([
            'bank_account_title' => 'Someone Else',
            'bank_account_number' => '99998888777766',
            'bank_account_last4' => '7766',
            'bank_verification_status' => BankVerificationStatus::MISMATCH,
        ]);
        $this->actingAsSellerOf($profile);

        $response = $this->post(self::SELLER.'/bank/statement', [
            'file' => UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonPath('data.bank.verification_status', 'pending_review');
        $response->assertJsonPath('data.bank.payout_ready', false);

        $stored = $profile->fresh();
        $this->assertNotNull($stored->bank_proof_path);
        Storage::disk('local')->assertExists($stored->bank_proof_path);

        Notification::assertSentTo($admin, SellerBankProofPendingNotification::class);
    }

    public function test_proof_cannot_be_uploaded_without_a_payout_account_or_when_already_verified(): void
    {
        $this->fakeDocumentVerifier();

        $noBank = $this->store([
            'bank_account_title' => null,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_last4' => null,
        ]);
        $this->actingAsSellerOf($noBank);
        $this->post(self::SELLER.'/bank/statement', [
            'file' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(409);

        $verified = $this->store(['bank_verification_status' => BankVerificationStatus::ADMIN_VERIFIED]);
        $this->actingAsSellerOf($verified);
        $this->post(self::SELLER.'/bank/statement', [
            'file' => UploadedFile::fake()->create('s.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(409);
    }

    // ==================================================================
    // P6-2 — bank proof: admin review
    // ==================================================================

    public function test_admin_can_download_the_uploaded_statement(): void
    {
        $profile = $this->store();
        $path = 'seller-bank-proofs/'.$profile->id.'/proof.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 fake');
        $profile->update([
            'bank_verification_status' => BankVerificationStatus::PENDING_REVIEW,
            'bank_proof_path' => $path,
            'bank_proof_original_name' => 'my statement.pdf',
            'bank_proof_uploaded_at' => now(),
        ]);
        $this->actingAsAdmin();

        $this->get(self::ADMIN."/{$profile->id}/bank/statement")
            ->assertOk()
            // Our own safe name, not the seller's file name (C14).
            ->assertDownload("store-{$profile->id}-bank-proof.pdf");
    }

    public function test_admin_verification_marks_the_account_payout_ready(): void
    {
        Notification::fake();
        $profile = $this->store([
            'bank_account_last4' => '1234',
            'bank_account_number' => '01234567891234',
        ]);
        $profile->update([
            'bank_verification_status' => BankVerificationStatus::PENDING_REVIEW,
            'bank_proof_path' => 'seller-bank-proofs/x.pdf',
            'bank_proof_uploaded_at' => now(),
        ]);
        $admin = $this->actingAsAdmin();

        $response = $this->postJson(self::ADMIN."/{$profile->id}/bank/review", ['decision' => 'verify']);

        $response->assertOk();
        $response->assertJsonPath('data.bank.verification_status', 'admin_verified');
        $response->assertJsonPath('data.bank.payout_ready', true);

        $fresh = $profile->fresh();
        $this->assertSame($admin->id, $fresh->bank_verified_by);
        $this->assertTrue($fresh->isPayoutBankVerified());

        Notification::assertSentTo(
            $profile->user,
            SellerBankProofReviewedNotification::class,
            fn (SellerBankProofReviewedNotification $n): bool => $n->verified === true,
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.bank.verified_by_admin']);
    }

    public function test_rejecting_the_statement_sends_the_reason_and_allows_another_try(): void
    {
        Notification::fake();
        $profile = $this->store(['bank_account_last4' => '1234']);
        $profile->update([
            'bank_verification_status' => BankVerificationStatus::PENDING_REVIEW,
            'bank_proof_path' => 'seller-bank-proofs/x.pdf',
            'bank_proof_uploaded_at' => now(),
        ]);
        $this->actingAsAdmin();

        $response = $this->postJson(self::ADMIN."/{$profile->id}/bank/review", [
            'decision' => 'reject',
            'reason' => 'The statement is cropped and the account title is not visible.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.bank.verification_status', 'mismatch');
        $response->assertJsonPath('data.bank.rejection_reason', 'The statement is cropped and the account title is not visible.');

        Notification::assertSentTo(
            $profile->user,
            SellerBankProofReviewedNotification::class,
            fn (SellerBankProofReviewedNotification $n): bool => $n->verified === false,
        );
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.bank.proof_rejected']);

        // Back to mismatch, so the seller can upload a better statement.
        $this->assertTrue($profile->fresh()->bank_verification_status->acceptsProof());
    }

    public function test_rejecting_without_a_reason_is_refused(): void
    {
        $profile = $this->store();
        $profile->update([
            'bank_verification_status' => BankVerificationStatus::PENDING_REVIEW,
            'bank_proof_path' => 'seller-bank-proofs/x.pdf',
            'bank_proof_uploaded_at' => now(),
        ]);
        $this->actingAsAdmin();

        $this->postJson(self::ADMIN."/{$profile->id}/bank/review", ['decision' => 'reject'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_reviewing_a_bank_with_nothing_pending_is_refused(): void
    {
        $profile = $this->store(['bank_verification_status' => BankVerificationStatus::MISMATCH]);
        $this->actingAsAdmin();

        $this->postJson(self::ADMIN."/{$profile->id}/bank/review", ['decision' => 'verify'])
            ->assertStatus(409);
        $this->get(self::ADMIN."/{$profile->id}/bank/statement")->assertStatus(409);
    }

    public function test_an_admin_cannot_verify_the_bank_of_their_own_store(): void
    {
        $admin = $this->actingAsAdmin('super_admin');
        $profile = SellerProfile::factory()->create(['user_id' => $admin->id]);
        $profile->update([
            'bank_verification_status' => BankVerificationStatus::PENDING_REVIEW,
            'bank_proof_path' => 'seller-bank-proofs/x.pdf',
            'bank_proof_uploaded_at' => now(),
        ]);

        $this->postJson(self::ADMIN."/{$profile->id}/bank/review", ['decision' => 'verify'])
            ->assertStatus(403);
    }

    public function test_changing_the_bank_account_again_clears_the_verification(): void
    {
        Notification::fake();
        $this->fakeDocumentVerifier();
        $profile = $this->store([
            'bank_account_title' => 'Zimal Fabrics Pvt Ltd',
            'bank_account_number' => '01234567891234',
            'bank_account_last4' => '1234',
            'bank_verification_status' => BankVerificationStatus::ADMIN_VERIFIED,
        ]);
        $this->actingAsSellerOf($profile);

        // A brand-new account must not inherit the old verification.
        $this->putJson(self::SELLER, [
            'bank_account_title' => 'Zimal Fabrics Pvt Ltd',
            'bank_name' => 'HBL',
            'bank_account_number' => '55554444333322',
        ])->assertOk();

        $this->assertFalse($profile->fresh()->isPayoutBankVerified());
    }
}
