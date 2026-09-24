<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Notifications\SellerBankDetailsChangedNotification;
use App\Models\AuditLog;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesGeminiModeration;
use Tests\TestCase;

final class SellerProfileTest extends TestCase
{
    use RefreshDatabase;
    use FakesGeminiModeration;

    private const BASE = '/api/v1/seller/profile';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');
    }

    /**
     * An approved seller: both roles (C4) + a live business.
     */
    private function actingAsSeller(array $profileOverrides = []): SellerProfile
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        $user->assignRole('seller');

        $profile = SellerProfile::factory()->create(['user_id' => $user->id] + $profileOverrides);

        Sanctum::actingAs($user);

        return $profile;
    }

    private function validBank(array $overrides = []): array
    {
        return array_merge([
            'bank_account_title' => 'Zimal Fabrics',
            'bank_name' => 'Meezan Bank',
            'bank_account_number' => '01234567891234',
        ], $overrides);
    }

    // ------------------------------------------------------------------
    // Access
    // ------------------------------------------------------------------

    public function test_a_customer_who_is_not_a_seller_gets_403(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $this->getJson(self::BASE)->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // Bank number never leaves the server + is encrypted at rest (D3)
    // ------------------------------------------------------------------

    public function test_profile_shows_the_bank_account_masked_and_never_the_full_number(): void
    {
        $profile = $this->actingAsSeller([
            'bank_account_number' => '01234567891234',
            'bank_account_last4' => '1234',
        ]);

        $response = $this->getJson(self::BASE);

        $response->assertOk();
        $response->assertJsonPath('data.store_name', $profile->store_name);
        $response->assertJsonPath('data.bank.account_number_masked', '****1234');
        $response->assertJsonPath('data.bank.is_set', true);
        $this->assertStringNotContainsString('01234567891234', $response->getContent());
    }

    public function test_bank_account_number_is_encrypted_in_the_database(): void
    {
        $profile = $this->actingAsSeller([
            'bank_account_number' => '01234567891234',
            'bank_account_last4' => '1234',
        ]);

        $raw = DB::table('seller_profiles')->where('id', $profile->id)->value('bank_account_number');

        $this->assertNotSame('01234567891234', $raw);
        $this->assertStringNotContainsString('01234567891234', (string) $raw);
        // ...but the model decrypts it back for the (future) payout code.
        $this->assertSame('01234567891234', $profile->fresh()->bank_account_number);
    }

    // ------------------------------------------------------------------
    // Editable fields
    // ------------------------------------------------------------------

    public function test_seller_can_update_description_and_address(): void
    {
        $this->fakeGeminiClean();
        $profile = $this->actingAsSeller();

        $response = $this->putJson(self::BASE, [
            'description' => 'Hand-made lawn and cotton fabrics.',
            'business_address' => 'Shop 12, Liberty Market, Lahore',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.description', 'Hand-made lawn and cotton fabrics.');

        $this->assertDatabaseHas('seller_profiles', [
            'id' => $profile->id,
            'business_address' => 'Shop 12, Liberty Market, Lahore',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.profile.updated',
            'subject_id' => $profile->user_id,
        ]);
    }

    public function test_verified_business_identity_fields_are_locked(): void
    {
        $profile = $this->actingAsSeller();

        $response = $this->putJson(self::BASE, [
            'store_name' => 'Renamed Store',
            'business_name' => 'Renamed Pvt Ltd',
            'business_type' => 'partnership',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['store_name', 'business_name', 'business_type']);

        $this->assertDatabaseHas('seller_profiles', [
            'id' => $profile->id,
            'store_name' => $profile->store_name,
        ]);
    }

    public function test_an_offensive_description_is_blocked_by_moderation(): void
    {
        $this->fakeGeminiFlagged([
            'description' => ['category' => 'hate_speech', 'reason' => 'Abusive language.'],
        ], 'high');
        $profile = $this->actingAsSeller(['description' => 'Original description']);

        $this->putJson(self::BASE, ['description' => 'something abusive'])
            ->assertStatus(422);

        $this->assertDatabaseHas('seller_profiles', [
            'id' => $profile->id,
            'description' => 'Original description',
        ]);
    }

    public function test_moderation_outage_returns_503_instead_of_crashing(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('', 500)]);
        $this->actingAsSeller();

        $response = $this->putJson(self::BASE, ['description' => 'Anything']);

        $response->assertStatus(503);
        // Internal details (API key names, provider errors) never reach the user.
        $this->assertStringNotContainsString('GEMINI', $response->getContent());
    }

    // ------------------------------------------------------------------
    // Bank details (C7, Q2)
    // ------------------------------------------------------------------

    public function test_adding_bank_details_the_first_time_emails_the_seller_and_is_audited_as_notice(): void
    {
        Notification::fake();
        $profile = $this->actingAsSeller([
            'bank_account_title' => null,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_last4' => null,
        ]);

        $response = $this->putJson(self::BASE, $this->validBank());

        $response->assertOk();
        $response->assertJsonPath('data.bank.account_number_masked', '****1234');

        Notification::assertSentTo(
            $profile->user,
            SellerBankDetailsChangedNotification::class,
            fn (SellerBankDetailsChangedNotification $n): bool => $n->isFirstTime === true && $n->maskedAccount === '****1234',
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'seller.bank_details.changed',
            'severity' => 'notice',
            'subject_id' => $profile->user_id,
        ]);
    }

    public function test_changing_bank_details_is_a_warning_and_the_audit_never_holds_the_full_number(): void
    {
        Notification::fake();
        $profile = $this->actingAsSeller([
            'bank_name' => 'HBL',
            'bank_account_number' => '11112222333344',
            'bank_account_last4' => '3344',
        ]);

        $this->putJson(self::BASE, $this->validBank())->assertOk();

        Notification::assertSentTo(
            $profile->user,
            SellerBankDetailsChangedNotification::class,
            fn (SellerBankDetailsChangedNotification $n): bool => $n->isFirstTime === false,
        );

        $audit = AuditLog::query()
            ->where('action', 'seller.bank_details.changed')
            ->firstOrFail();

        $this->assertSame('warning', $audit->severity->value);
        $this->assertSame('3344', $audit->old_values['account_last4']);
        $this->assertSame('1234', $audit->new_values['account_last4']);

        $auditJson = json_encode([$audit->old_values, $audit->new_values]);
        $this->assertStringNotContainsString('11112222333344', $auditJson);
        $this->assertStringNotContainsString('01234567891234', $auditJson);
    }

    public function test_sending_the_same_bank_details_again_is_not_a_change(): void
    {
        Notification::fake();
        $this->actingAsSeller([
            'bank_account_title' => 'Zimal Fabrics',
            'bank_name' => 'Meezan Bank',
            'bank_account_number' => '01234567891234',
            'bank_account_last4' => '1234',
        ]);

        $this->putJson(self::BASE, $this->validBank())->assertOk();

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'seller.bank_details.changed']);
    }

    public function test_an_iban_is_normalised_before_saving(): void
    {
        Notification::fake();
        $profile = $this->actingAsSeller();

        $this->putJson(self::BASE, $this->validBank([
            'bank_account_number' => 'pk36 scbl 0000-0011 2345 6702',
        ]))->assertOk();

        $fresh = $profile->fresh();
        $this->assertSame('PK36SCBL0000001123456702', $fresh->bank_account_number);
        $this->assertSame('6702', $fresh->bank_account_last4);
    }

    public function test_invalid_or_incomplete_bank_details_are_refused(): void
    {
        $this->actingAsSeller();

        // Letters in a plain account number.
        $this->putJson(self::BASE, $this->validBank(['bank_account_number' => '12AB34CD']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account_number']);

        // Number without title / bank name — the three go together.
        $this->putJson(self::BASE, ['bank_account_number' => '01234567891234'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bank_account_title', 'bank_name']);
    }

    // ------------------------------------------------------------------
    // Logo
    // ------------------------------------------------------------------

    public function test_seller_can_upload_and_replace_the_store_logo(): void
    {
        $this->fakeGeminiClean();
        $profile = $this->actingAsSeller();

        $this->post(self::BASE.'/logo', ['logo' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])
            ->assertOk();
        $firstPath = $profile->fresh()->logo_path;
        Storage::disk('public')->assertExists($firstPath);

        $response = $this->post(self::BASE.'/logo', ['logo' => UploadedFile::fake()->image('logo2.png')], ['Accept' => 'application/json']);

        $response->assertOk();
        $this->assertNotNull($response->json('data.logo_url'));

        $secondPath = $profile->fresh()->logo_path;
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_seller_can_remove_the_store_logo(): void
    {
        $this->fakeGeminiClean();
        $profile = $this->actingAsSeller();

        $this->post(self::BASE.'/logo', ['logo' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])
            ->assertOk();
        $path = $profile->fresh()->logo_path;

        $response = $this->deleteJson(self::BASE.'/logo');

        $response->assertOk();
        $response->assertJsonPath('data.logo_url', null);
        Storage::disk('public')->assertMissing($path);
    }
}
