<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Notifications\SellerApplicationApprovedNotification;
use App\Domain\Seller\Notifications\SellerApplicationRejectedNotification;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AdminSellerApplicationTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/admin/seller-applications';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    private function actingAsAdmin(string $role = 'platform_admin'): User
    {
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Sanctum::actingAs($admin);

        return $admin;
    }

    private function applicant(): User
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        return $user;
    }

    private function pendingApplication(?User $applicant = null): SellerApplication
    {
        $application = SellerApplication::factory()
            ->pending()
            ->create(['user_id' => ($applicant ?? $this->applicant())->id]);

        foreach (SellerDocumentType::cases() as $type) {
            SellerApplicationDocument::factory()
                ->ofType($type)
                ->create(['seller_application_id' => $application->id]);
        }

        return $application;
    }

    // ------------------------------------------------------------------
    // Access (D10)
    // ------------------------------------------------------------------

    public function test_a_customer_cannot_use_the_admin_endpoints(): void
    {
        $customer = $this->applicant();
        Sanctum::actingAs($customer);

        $this->getJson(self::BASE)->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // List
    // ------------------------------------------------------------------

    public function test_platform_admin_sees_only_pending_applications_by_default(): void
    {
        $this->actingAsAdmin('platform_admin');

        SellerApplication::factory()->create();              // draft
        $pending = $this->pendingApplication();
        SellerApplication::factory()->approved()->create();

        $response = $this->getJson(self::BASE);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $pending->id);
        $response->assertJsonPath('data.0.status', 'pending');
        $response->assertJsonPath('data.0.documents_count', 5);
        $response->assertJsonPath('data.0.applicant.email', $pending->user->email);
    }

    public function test_drafts_are_never_listed_even_with_status_all(): void
    {
        $this->actingAsAdmin();

        SellerApplication::factory()->create();              // draft — must stay hidden
        $this->pendingApplication();
        SellerApplication::factory()->rejected()->create();

        $response = $this->getJson(self::BASE.'?status=all');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $this->assertNotContains('draft', array_column($response->json('data'), 'status'));
    }

    public function test_asking_for_drafts_is_a_validation_error(): void
    {
        $this->actingAsAdmin();

        $this->getJson(self::BASE.'?status=draft')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ------------------------------------------------------------------
    // Detail + documents
    // ------------------------------------------------------------------

    public function test_detail_shows_documents_with_download_links_and_never_file_paths(): void
    {
        $this->actingAsAdmin();
        $application = $this->pendingApplication();

        $response = $this->getJson(self::BASE.'/'.$application->id);

        $response->assertOk();
        $response->assertJsonCount(5, 'data.documents');
        $response->assertJsonPath('data.applicant.email', $application->user->email);
        $response->assertJsonMissingPath('data.documents.0.file_path');
        $this->assertStringContainsString(
            "/api/v1/admin/seller-applications/{$application->id}/documents/",
            $response->json('data.documents.0.download_url'),
        );
    }

    public function test_admin_can_download_a_private_document(): void
    {
        $this->actingAsAdmin();
        $application = SellerApplication::factory()->pending()->create();

        $path = "seller-documents/{$application->id}/abc123.pdf";
        Storage::disk('local')->put($path, '%PDF-1.4 fake content');

        SellerApplicationDocument::factory()
            ->ofType(SellerDocumentType::CNIC_FRONT)
            ->create([
                'seller_application_id' => $application->id,
                'file_path' => $path,
            ]);

        $response = $this->get(self::BASE."/{$application->id}/documents/cnic_front");

        $response->assertOk();
        // Our own safe name — never the customer's original file name.
        $response->assertDownload("application-{$application->id}-cnic_front.pdf");
    }

    public function test_downloading_a_document_that_was_never_uploaded_is_404(): void
    {
        $this->actingAsAdmin();
        $application = SellerApplication::factory()->pending()->create();

        $this->getJson(self::BASE."/{$application->id}/documents/bank_statement")->assertStatus(404);
    }

    public function test_an_unknown_document_type_is_404(): void
    {
        $this->actingAsAdmin();
        $application = $this->pendingApplication();

        $this->getJson(self::BASE."/{$application->id}/documents/passport")->assertStatus(404);
    }

    public function test_an_unknown_application_is_404(): void
    {
        $this->actingAsAdmin();

        $this->getJson(self::BASE.'/999999')->assertStatus(404);
        $this->postJson(self::BASE.'/999999/review', ['decision' => 'approve'])->assertStatus(404);
    }

    // ------------------------------------------------------------------
    // Approve (C3, C4, C11)
    // ------------------------------------------------------------------

    public function test_approving_creates_the_business_grants_seller_role_and_keeps_customer_role(): void
    {
        Notification::fake();

        $admin = $this->actingAsAdmin();
        $applicant = $this->applicant();
        $application = $this->pendingApplication($applicant);

        $response = $this->postJson(self::BASE."/{$application->id}/review", ['decision' => 'approve']);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');
        $response->assertJsonPath('data.reviewed_by.id', $admin->id);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $applicant->id,
            'seller_application_id' => $application->id,
            'store_name' => $application->store_name,
            'status' => 'active',
        ]);

        $applicant = $applicant->fresh();
        $this->assertTrue($applicant->hasRole('seller'));
        $this->assertTrue($applicant->hasRole('customer'));        // C4
        $this->assertTrue($applicant->can('panel.seller.access')); // C11

        Notification::assertSentTo($applicant, SellerApplicationApprovedNotification::class);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.application.approved',
            'subject_id' => $applicant->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Reject
    // ------------------------------------------------------------------

    public function test_rejecting_without_a_reason_is_refused(): void
    {
        $this->actingAsAdmin();
        $application = $this->pendingApplication();

        $this->postJson(self::BASE."/{$application->id}/review", ['decision' => 'reject'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertDatabaseHas('seller_applications', ['id' => $application->id, 'status' => 'pending']);
    }

    public function test_rejecting_saves_the_reason_and_emails_the_applicant(): void
    {
        Notification::fake();

        $this->actingAsAdmin();
        $applicant = $this->applicant();
        $application = $this->pendingApplication($applicant);

        $response = $this->postJson(self::BASE."/{$application->id}/review", [
            'decision' => 'reject',
            'reason' => 'CNIC back side is blurry, please upload a clear photo.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'rejected');
        $response->assertJsonPath('data.rejection_reason', 'CNIC back side is blurry, please upload a clear photo.');

        // No business, no seller role on a rejection.
        $this->assertDatabaseMissing('seller_profiles', ['user_id' => $applicant->id]);
        $this->assertFalse($applicant->fresh()->hasRole('seller'));

        Notification::assertSentTo(
            $applicant,
            SellerApplicationRejectedNotification::class,
            fn (SellerApplicationRejectedNotification $notification): bool => $notification->reason === 'CNIC back side is blurry, please upload a clear photo.',
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.application.rejected',
            'subject_id' => $applicant->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Guards
    // ------------------------------------------------------------------

    public function test_an_already_reviewed_application_cannot_be_reviewed_again(): void
    {
        $this->actingAsAdmin();
        $application = SellerApplication::factory()->approved()->create();

        $this->postJson(self::BASE."/{$application->id}/review", ['decision' => 'approve'])
            ->assertStatus(409);
    }

    public function test_a_draft_cannot_be_reviewed(): void
    {
        $this->actingAsAdmin();
        $application = SellerApplication::factory()->create(); // draft

        $this->postJson(self::BASE."/{$application->id}/review", ['decision' => 'approve'])
            ->assertStatus(409);
    }

    public function test_an_admin_cannot_review_their_own_application(): void
    {
        $admin = $this->actingAsAdmin('super_admin');
        $application = $this->pendingApplication($admin);

        $this->postJson(self::BASE."/{$application->id}/review", ['decision' => 'approve'])
            ->assertStatus(403);

        $this->assertDatabaseHas('seller_applications', ['id' => $application->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('seller_profiles', ['user_id' => $admin->id]);
    }
}
