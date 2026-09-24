<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerRenewalStatus;
use App\Models\SellerApplicationDocument;
use App\Models\SellerDocumentRenewal;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9a — KYC retention (BLUEPRINT section 14a).
 */
final class SellerKycRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    private function store(array $overrides = []): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        return SellerProfile::factory()->create(['user_id' => $owner->id] + $overrides);
    }

    /** A renewal whose file really exists on the fake disk. */
    private function renewal(SellerProfile $store, array $overrides = []): SellerDocumentRenewal
    {
        $path = 'seller-renewals/'.$store->id.'/'.uniqid().'.pdf';
        Storage::disk('local')->put($path, 'pretend-scan');

        return SellerDocumentRenewal::factory()->create([
            'seller_profile_id' => $store->id,
            'file_path' => $path,
        ] + $overrides);
    }

    private function sweep(): void
    {
        $this->artisan('seller:purge-expired-kyc-documents')->assertSuccessful();
    }

    // ==================================================================
    // Superseded evidence
    // ==================================================================

    public function test_a_rejected_renewal_is_purged_once_its_window_passes(): void
    {
        $store = $this->store();
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::REJECTED->value,
            'updated_at' => now()->subDays(91),
        ]);

        $this->sweep();

        $renewal->refresh();
        $this->assertNotNull($renewal->file_purged_at);
        $this->assertFalse(Storage::disk('local')->exists($renewal->file_path));

        // P9-2 — the ROW survives. It still says a document existed.
        $this->assertDatabaseHas('seller_document_renewals', ['id' => $renewal->id]);
    }

    public function test_a_recent_rejection_is_left_alone(): void
    {
        $store = $this->store();
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::REJECTED->value,
            'updated_at' => now()->subDays(10),
        ]);

        $this->sweep();

        $this->assertNull($renewal->refresh()->file_purged_at);
        $this->assertTrue(Storage::disk('local')->exists($renewal->file_path));
    }

    public function test_the_newest_approved_renewal_is_never_purged_but_the_one_it_replaced_is(): void
    {
        $store = $this->store();

        $old = $this->renewal($store, [
            'document_type' => SellerDocumentType::CNIC_FRONT->value,
            'status' => SellerRenewalStatus::APPROVED->value,
            'updated_at' => now()->subDays(200),
        ]);
        $current = $this->renewal($store, [
            'document_type' => SellerDocumentType::CNIC_FRONT->value,
            'status' => SellerRenewalStatus::APPROVED->value,
            'updated_at' => now()->subDays(150),
        ]);

        $this->sweep();

        // The superseded one goes.
        $this->assertNotNull($old->refresh()->file_purged_at);
        $this->assertFalse(Storage::disk('local')->exists($old->file_path));

        // The current evidence stays, however old it is.
        $this->assertNull($current->refresh()->file_purged_at);
        $this->assertTrue(Storage::disk('local')->exists($current->file_path));
    }

    public function test_a_lone_approved_renewal_is_current_evidence_and_stays(): void
    {
        $store = $this->store();
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::APPROVED->value,
            'updated_at' => now()->subDays(900),
        ]);

        $this->sweep();

        $this->assertNull($renewal->refresh()->file_purged_at);
    }

    /** C42 — destroying evidence mid-investigation is the worst timing. */
    public function test_a_suspended_stores_documents_are_never_purged(): void
    {
        $store = $this->store([
            'status' => SellerProfileStatus::SUSPENDED->value,
            'suspended_at' => now(),
            'suspension_reason' => 'Under investigation.',
        ]);
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::REJECTED->value,
            'updated_at' => now()->subDays(400),
        ]);

        $this->sweep();

        $this->assertNull($renewal->refresh()->file_purged_at);
        $this->assertTrue(Storage::disk('local')->exists($renewal->file_path));
    }

    // ==================================================================
    // Closed stores
    // ==================================================================

    /** C41 — closed_at starts the clock, nothing else. */
    public function test_an_active_stores_onboarding_documents_are_never_purged(): void
    {
        $store = $this->store();
        $path = 'seller-documents/'.$store->seller_application_id.'/cnic.pdf';
        Storage::disk('local')->put($path, 'scan');
        $doc = SellerApplicationDocument::factory()->create([
            'seller_application_id' => $store->seller_application_id,
            'file_path' => $path,
        ]);

        $this->sweep();

        $this->assertNull($doc->refresh()->file_purged_at);
        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_a_long_closed_stores_documents_are_purged(): void
    {
        $store = $this->store([
            'status' => SellerProfileStatus::CLOSED->value,
            'closed_at' => now()->subDays(1826),
        ]);

        $path = 'seller-documents/'.$store->seller_application_id.'/cnic.pdf';
        Storage::disk('local')->put($path, 'scan');
        $doc = SellerApplicationDocument::factory()->create([
            'seller_application_id' => $store->seller_application_id,
            'file_path' => $path,
        ]);
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::APPROVED->value,
            'updated_at' => now()->subDays(1),
        ]);

        $this->sweep();

        $this->assertNotNull($doc->refresh()->file_purged_at);
        $this->assertFalse(Storage::disk('local')->exists($path));

        // Even current evidence goes once the relationship itself is over.
        $this->assertNotNull($renewal->refresh()->file_purged_at);

        // P9-2 — every row is still there.
        $this->assertDatabaseHas('seller_application_documents', ['id' => $doc->id]);
        $this->assertDatabaseHas('seller_document_renewals', ['id' => $renewal->id]);
    }

    public function test_a_recently_closed_store_keeps_its_documents(): void
    {
        $store = $this->store([
            'status' => SellerProfileStatus::CLOSED->value,
            'closed_at' => now()->subDays(30),
        ]);
        $path = 'seller-documents/'.$store->seller_application_id.'/cnic.pdf';
        Storage::disk('local')->put($path, 'scan');
        $doc = SellerApplicationDocument::factory()->create([
            'seller_application_id' => $store->seller_application_id,
            'file_path' => $path,
        ]);

        $this->sweep();

        $this->assertNull($doc->refresh()->file_purged_at);
    }

    /** C43 — file first, stamp second, and running twice changes nothing. */
    public function test_the_sweep_is_idempotent_and_survives_a_missing_file(): void
    {
        $store = $this->store();
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::REJECTED->value,
            'updated_at' => now()->subDays(120),
        ]);

        // Simulate a half-finished earlier run: the file is already gone
        // but the row was never stamped.
        Storage::disk('local')->delete($renewal->file_path);

        $this->sweep();
        $first = $renewal->refresh()->file_purged_at;
        $this->assertNotNull($first);

        $this->sweep();
        $this->assertEquals($first, $renewal->refresh()->file_purged_at);
    }

    public function test_the_retention_windows_come_from_config(): void
    {
        config(['seller.superseded_retention_days' => 5]);

        $store = $this->store();
        $renewal = $this->renewal($store, [
            'status' => SellerRenewalStatus::REJECTED->value,
            'updated_at' => now()->subDays(6),
        ]);

        $this->sweep();

        // With the default 90 days this would have survived.
        $this->assertNotNull($renewal->refresh()->file_purged_at);
    }
}
