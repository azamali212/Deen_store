<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Notifications\SellerApplicationSubmittedNotification;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class SellerApplicationTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/seller/application';

    protected function setUp(): void
    {
        parent::setUp();

        // Real roles + permissions (role:customer middleware, admin lookup).
        $this->seed(RoleAndPermissionSeeder::class);

        // KYC documents go to the PRIVATE 'local' disk (D7) — faked here so
        // nothing touches the real storage folder.
        Storage::fake('local');
    }

    private function actingAsCustomer(?User $user = null): User
    {
        $user ??= User::factory()->create();
        $user->assignRole('customer');

        Sanctum::actingAs($user);

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'store_name' => 'Zimal Fabrics',
            'business_name' => 'Zimal Fabrics Pvt Ltd',
            'business_type' => 'company',
            'accept_document_processing' => true,
        ], $overrides);
    }

    private function uploadDocument(SellerDocumentType $type, ?UploadedFile $file = null)
    {
        return $this->postJson(self::BASE.'/documents', [
            'document_type' => $type->value,
            'file' => $file ?? UploadedFile::fake()->create($type->value.'.pdf', 200, 'application/pdf'),
        ]);
    }

    private function giveApplicationAllDocuments(SellerApplication $application): void
    {
        foreach (SellerDocumentType::cases() as $type) {
            SellerApplicationDocument::factory()
                ->ofType($type)
                ->create(['seller_application_id' => $application->id]);
        }
    }

    // ------------------------------------------------------------------
    // Access
    // ------------------------------------------------------------------

    public function test_guest_cannot_start_an_application(): void
    {
        $this->postJson(self::BASE, $this->validPayload())->assertStatus(401);
    }

    public function test_customer_with_unverified_email_cannot_apply(): void
    {
        $this->actingAsCustomer(User::factory()->unverified()->create());

        $this->postJson(self::BASE, $this->validPayload())->assertStatus(403);

        $this->assertDatabaseCount('seller_applications', 0);
    }

    // ------------------------------------------------------------------
    // Create
    // ------------------------------------------------------------------

    public function test_customer_can_start_an_application_as_a_draft(): void
    {
        $user = $this->actingAsCustomer();

        $response = $this->postJson(self::BASE, $this->validPayload());

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.store_name', 'Zimal Fabrics');
        $response->assertJsonCount(5, 'data.missing_documents');

        $this->assertDatabaseHas('seller_applications', [
            'user_id' => $user->id,
            'store_name' => 'Zimal Fabrics',
            'status' => 'draft',
        ]);
    }

    public function test_starting_an_application_requires_consent_to_document_checks(): void
    {
        $this->actingAsCustomer();

        $this->postJson(self::BASE, $this->validPayload(['accept_document_processing' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accept_document_processing']);

        $this->assertDatabaseCount('seller_applications', 0);
    }

    public function test_consent_time_is_recorded(): void
    {
        $user = $this->actingAsCustomer();

        $this->postJson(self::BASE, $this->validPayload())->assertCreated();

        $this->assertNotNull(SellerApplication::query()->where('user_id', $user->id)->value('document_processing_consent_at'));
    }

    public function test_customer_cannot_start_a_second_application(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->create(['user_id' => $user->id]);

        $this->postJson(self::BASE, $this->validPayload())->assertStatus(409);
    }

    public function test_store_name_must_be_unique(): void
    {
        SellerApplication::factory()->create(['store_name' => 'Zimal Fabrics']);
        $this->actingAsCustomer();

        $response = $this->postJson(self::BASE, $this->validPayload());

        $response->assertStatus(422);
        $response->assertJsonPath('errors.store_name.0', "The store name 'Zimal Fabrics' is already taken.");
    }

    // ------------------------------------------------------------------
    // Documents
    // ------------------------------------------------------------------

    public function test_customer_can_upload_a_document_to_the_private_disk(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->create(['user_id' => $user->id]);

        $response = $this->uploadDocument(SellerDocumentType::CNIC_FRONT);

        $response->assertCreated();
        $response->assertJsonPath('data.document_type', 'cnic_front');
        // The private storage path must never reach the client.
        $response->assertJsonMissingPath('data.file_path');

        $document = SellerApplicationDocument::query()->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_reuploading_a_document_replaces_it_and_deletes_the_old_file(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->create(['user_id' => $user->id]);

        $this->uploadDocument(SellerDocumentType::CNIC_FRONT)->assertCreated();
        $oldPath = SellerApplicationDocument::query()->firstOrFail()->file_path;

        $this->uploadDocument(SellerDocumentType::CNIC_FRONT)->assertOk();

        $this->assertDatabaseCount('seller_application_documents', 1);

        $newPath = SellerApplicationDocument::query()->firstOrFail()->file_path;
        $this->assertNotSame($oldPath, $newPath);

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_upload_rejects_a_disallowed_file_type(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->create(['user_id' => $user->id]);

        $response = $this->uploadDocument(
            SellerDocumentType::CNIC_FRONT,
            UploadedFile::fake()->create('virus.exe', 50, 'application/x-msdownload'),
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_a_file_over_10_mb(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->create(['user_id' => $user->id]);

        $response = $this->uploadDocument(
            SellerDocumentType::BANK_STATEMENT,
            UploadedFile::fake()->create('statement.pdf', 10241, 'application/pdf'),
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    // ------------------------------------------------------------------
    // Submit
    // ------------------------------------------------------------------

    public function test_submit_is_refused_until_all_five_documents_are_uploaded(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->create(['user_id' => $user->id]);

        $this->uploadDocument(SellerDocumentType::CNIC_FRONT)->assertCreated();

        $response = $this->postJson(self::BASE.'/submit');

        $response->assertStatus(422);
        $response->assertJsonCount(4, 'missing_documents');

        $this->assertDatabaseHas('seller_applications', ['user_id' => $user->id, 'status' => 'draft']);
    }

    public function test_submitting_a_complete_application_notifies_admins_and_is_audited(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('platform_admin');

        $user = $this->actingAsCustomer();
        $application = SellerApplication::factory()->create(['user_id' => $user->id]);
        $this->giveApplicationAllDocuments($application);

        $response = $this->postJson(self::BASE.'/submit');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'pending');
        $this->assertNotNull($application->fresh()->submitted_at);

        Notification::assertSentTo(
            $admin,
            SellerApplicationSubmittedNotification::class,
            fn (SellerApplicationSubmittedNotification $notification): bool => $notification->isResubmission === false,
        );
        // The applicant is not an admin and must not get the admin email.
        Notification::assertNotSentTo($user, SellerApplicationSubmittedNotification::class);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.application.submitted',
            'subject_id' => $user->id,
        ]);
    }

    public function test_pending_application_cannot_be_edited_or_given_new_documents(): void
    {
        $user = $this->actingAsCustomer();
        SellerApplication::factory()->pending()->create(['user_id' => $user->id]);

        $this->patchJson(self::BASE, ['business_name' => 'Changed'])->assertStatus(409);
        $this->uploadDocument(SellerDocumentType::CNIC_FRONT)->assertStatus(409);
        $this->postJson(self::BASE.'/submit')->assertStatus(409);
    }

    // ------------------------------------------------------------------
    // Rejected -> fix -> resubmit (D2)
    // ------------------------------------------------------------------

    public function test_rejected_application_can_be_fixed_and_resubmitted(): void
    {
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $user = $this->actingAsCustomer();
        $application = SellerApplication::factory()
            ->rejected('CNIC back side is blurry.')
            ->create(['user_id' => $user->id]);
        $this->giveApplicationAllDocuments($application);

        // Fix: replace the flagged document, then resubmit.
        $this->uploadDocument(SellerDocumentType::CNIC_BACK)->assertOk();

        $response = $this->postJson(self::BASE.'/submit');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'pending');
        $response->assertJsonPath('data.rejection_reason', null);

        $fresh = $application->fresh();
        $this->assertSame(SellerApplicationStatus::PENDING, $fresh->status);
        $this->assertNull($fresh->reviewed_by);

        // Still ONE application row — the same one was edited (D2).
        $this->assertDatabaseCount('seller_applications', 1);

        Notification::assertSentTo(
            $admin,
            SellerApplicationSubmittedNotification::class,
            fn (SellerApplicationSubmittedNotification $notification): bool => $notification->isResubmission === true,
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.application.resubmitted',
            'subject_id' => $user->id,
        ]);
    }

    // ------------------------------------------------------------------
    // View
    // ------------------------------------------------------------------

    public function test_customer_sees_404_before_starting_an_application(): void
    {
        $this->actingAsCustomer();

        $this->getJson(self::BASE)->assertStatus(404);
    }

    public function test_customer_can_view_their_application_with_missing_documents(): void
    {
        $user = $this->actingAsCustomer();
        $application = SellerApplication::factory()->create(['user_id' => $user->id]);
        SellerApplicationDocument::factory()
            ->ofType(SellerDocumentType::CNIC_FRONT)
            ->create(['seller_application_id' => $application->id]);

        $response = $this->getJson(self::BASE);

        $response->assertOk();
        $response->assertJsonCount(1, 'data.documents');
        $response->assertJsonCount(4, 'data.missing_documents');
        $response->assertJsonMissingPath('data.documents.0.file_path');
    }
}
