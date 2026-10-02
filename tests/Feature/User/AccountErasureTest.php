<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Domain\Auth\Enums\UserAccountStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\User\Contracts\AvatarStorageInterface;
use App\Domain\User\Enums\ConsentType;
use App\Domain\User\Notifications\AccountErasureCancelledNotification;
use App\Domain\User\Notifications\AccountErasureScheduledNotification;
use App\Domain\User\Services\AccountErasureService;
use App\Domain\User\Services\ConsentService;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserProfile;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * A5 — GDPR Art. 17 erasure (BLUEPRINT section 18, P12).
 */
final class AccountErasureTest extends TestCase
{
    use RefreshDatabase;

    private const ERASURE = '/api/v1/privacy/erasure';

    private const PHRASE = 'DELETE MY ACCOUNT';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'password' => 'password',
            'confirmation' => self::PHRASE,
        ], $overrides);
    }

    private function store(SellerProfileStatus $status = SellerProfileStatus::ACTIVE): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        // seller_profiles.user_id is UNIQUE — one store per owner, so a test
        // must never create a second profile for the same user.
        return SellerProfile::factory()->create([
            'user_id' => $owner->id,
            'status' => $status,
        ]);
    }

    private function storeOwner(SellerProfileStatus $status = SellerProfileStatus::ACTIVE): User
    {
        return $this->store($status)->user()->first();
    }

    // ------------------------------------------------------------- guards

    public function test_guest_is_rejected(): void
    {
        $this->postJson(self::ERASURE, $this->payload())->assertStatus(401);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::ERASURE, $this->payload(['password' => 'not-my-password']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertNull($user->fresh()->erasure_requested_at);
    }

    public function test_the_confirmation_phrase_must_be_typed_exactly(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::ERASURE, $this->payload(['confirmation' => 'delete my account']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirmation');

        $this->assertNull($user->fresh()->erasure_requested_at);
    }

    // ------------------------------------------------------------ request

    public function test_a_valid_request_schedules_the_erasure_and_sends_the_last_email(): void
    {
        Notification::fake();
        config(['privacy.erasure_grace_days' => 14]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(self::ERASURE, $this->payload())->assertStatus(202);

        $user->refresh();

        $this->assertNotNull($user->erasure_requested_at);
        $this->assertNull($user->erased_at);
        $this->assertSame(
            $user->erasure_requested_at->copy()->addDays(14)->toIso8601String(),
            $response->json('data.scheduled_for'),
        );

        // C65 — sent NOW, while there is still an address to send to.
        Notification::assertSentTo($user, AccountErasureScheduledNotification::class);
    }

    public function test_requesting_twice_is_a_conflict(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::ERASURE, $this->payload())->assertStatus(202);

        $this->postJson(self::ERASURE, $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['scheduled_for']);
    }

    public function test_an_open_store_owner_must_close_the_store_first(): void
    {
        $owner = $this->storeOwner();
        Sanctum::actingAs($owner);

        $response = $this->postJson(self::ERASURE, $this->payload())->assertStatus(409);

        $this->assertNotEmpty($response->json('reasons'));
        $this->assertStringContainsString('Close the store first', $response->json('reasons.0'));
        $this->assertNull($owner->fresh()->erasure_requested_at);
    }

    public function test_a_closed_store_owner_may_be_erased(): void
    {
        $owner = $this->storeOwner(SellerProfileStatus::CLOSED);
        Sanctum::actingAs($owner);

        $this->postJson(self::ERASURE, $this->payload())->assertStatus(202);
    }

    public function test_a_team_member_who_owns_nothing_is_not_blocked_by_someone_elses_store(): void
    {
        $store = $this->store();

        $staff = User::factory()->create();
        $staff->assignRole('customer');
        $staff->assignRole(SellerTeamRole::STAFF->systemRole()->value);

        SellerTeamMember::factory()->role(SellerTeamRole::STAFF)->create([
            'seller_profile_id' => $store->id,
            'user_id' => $staff->id,
            'invited_by' => $store->user_id,
        ]);

        Sanctum::actingAs($staff);

        $this->postJson(self::ERASURE, $this->payload())->assertStatus(202);
    }

    public function test_the_status_endpoint_explains_why_it_is_blocked(): void
    {
        Sanctum::actingAs($this->storeOwner());

        $this->getJson(self::ERASURE)
            ->assertOk()
            ->assertJsonPath('data.pending', false)
            ->assertJsonPath('data.confirmation_phrase', self::PHRASE)
            ->assertJsonCount(1, 'data.blocked_by');
    }

    // ------------------------------------------------------------- cancel

    public function test_the_user_can_cancel_during_the_grace_period(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::ERASURE, $this->payload())->assertStatus(202);
        $this->deleteJson(self::ERASURE)->assertOk();

        $this->assertNull($user->fresh()->erasure_requested_at);

        Notification::assertSentTo($user, AccountErasureCancelledNotification::class);
    }

    public function test_cancelling_with_nothing_pending_is_a_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson(self::ERASURE)->assertStatus(404);
    }

    // -------------------------------------------------------------- sweep

    public function test_the_sweep_leaves_accounts_still_inside_the_grace_period_alone(): void
    {
        config(['privacy.erasure_grace_days' => 14]);

        $user = User::factory()->create();
        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subDays(13)])->save();

        $result = app(AccountErasureService::class)->processDue();

        $this->assertSame(0, $result['erased']);
        $this->assertNull($user->fresh()->erased_at);
    }

    public function test_the_sweep_anonymises_the_account_in_place(): void
    {
        config(['privacy.erasure_grace_days' => 14]);

        $user = User::factory()->create([
            'name' => 'Azam Ali',
            'email' => 'azam@example.com',
            'phone' => '+923001234567',
        ]);
        $user->assignRole('customer');

        UserProfile::factory()->create([
            'user_id' => $user->id,
            'avatar_path' => 'avatars/1/photo.jpg',
        ]);
        UserAddress::factory()->create(['user_id' => $user->id]);
        app(ConsentService::class)->grant($user, ConsentType::TERMS);
        $user->createToken('phone');

        // The file must go, and it must go before the row that points at it.
        $this->mock(AvatarStorageInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('delete')->once()->with('avatars/1/photo.jpg');
        });

        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subDays(15)])->save();

        $result = app(AccountErasureService::class)->processDue();

        $this->assertSame(1, $result['erased']);

        // The ROW survives — orders, audit rows and consent rows point at it.
        $erased = User::withTrashed()->find($user->id);

        $this->assertNotNull($erased);
        $this->assertNotNull($erased->erased_at);
        $this->assertNotNull($erased->deleted_at);

        $this->assertSame('Deleted user', $erased->name);
        $this->assertSame('erased-'.$erased->uuid.'@erased.invalid', $erased->email);
        $this->assertNull($erased->phone);
        $this->assertNull($erased->email_verified_at);
        $this->assertSame(UserAccountStatus::INACTIVE, $erased->status);

        // Personal data gone.
        $this->assertDatabaseMissing('user_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $user->id]);

        // Access gone.
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
        $this->assertSame(0, $erased->roles()->count());

        // C61 — the consent record is the evidence we were ALLOWED to hold
        // the data. Deleting it with the data would destroy the defence.
        $this->assertDatabaseHas('user_consents', [
            'user_id' => $user->id,
            'type' => ConsentType::TERMS->value,
        ]);
    }

    public function test_the_old_password_no_longer_opens_the_erased_account(): void
    {
        config(['privacy.erasure_grace_days' => 0]);

        $user = User::factory()->create(['email' => 'gone@example.com']);
        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subMinute()])->save();

        app(AccountErasureService::class)->processDue();

        $erased = User::withTrashed()->find($user->id);

        // Three independent locks, because one of them failing silently
        // would leave a way back into a "deleted" account: the password no
        // longer matches anything anyone knows, the status cannot log in,
        // and the row is soft-deleted so the login lookup never finds it.
        $this->assertFalse(Hash::check('password', (string) $erased->password));
        $this->assertFalse($erased->status->canLogin());
        $this->assertNotNull($erased->deleted_at);
        $this->assertSame(0, User::query()->where('email', 'gone@example.com')->count());
    }

    public function test_the_sweep_is_idempotent(): void
    {
        config(['privacy.erasure_grace_days' => 0]);

        $user = User::factory()->create();
        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subMinute()])->save();

        $this->assertSame(1, app(AccountErasureService::class)->processDue()['erased']);
        $this->assertSame(0, app(AccountErasureService::class)->processDue()['erased']);
    }

    public function test_a_blocker_that_appears_during_the_grace_period_cancels_the_request(): void
    {
        Notification::fake();
        config(['privacy.erasure_grace_days' => 14]);

        $user = User::factory()->create();
        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subDays(20)])->save();

        // C63 — they opened a store after asking to be erased. The request can
        // no longer be honoured, so it is cancelled and they are told, rather
        // than sitting in the queue forever.
        $user->assignRole('seller');
        SellerProfile::factory()->create([
            'user_id' => $user->id,
            'status' => SellerProfileStatus::ACTIVE,
        ]);

        $result = app(AccountErasureService::class)->processDue();

        $this->assertSame(0, $result['erased']);
        $this->assertSame(1, $result['cancelled']);
        $this->assertNull($user->fresh()->erasure_requested_at);
        $this->assertNull($user->fresh()->erased_at);

        Notification::assertSentTo($user, AccountErasureCancelledNotification::class);
    }

    public function test_an_erased_user_is_removed_from_store_teams(): void
    {
        config(['privacy.erasure_grace_days' => 0]);

        $store = $this->store(SellerProfileStatus::CLOSED);

        $staff = User::factory()->create();
        SellerTeamMember::factory()->role(SellerTeamRole::STAFF)->create([
            'seller_profile_id' => $store->id,
            'user_id' => $staff->id,
            'invited_by' => $store->user_id,
        ]);

        $staff->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subMinute()])->save();

        app(AccountErasureService::class)->processDue();

        $this->assertDatabaseMissing('seller_team_members', ['user_id' => $staff->id]);
    }

    public function test_the_console_command_reports_what_it_did(): void
    {
        config(['privacy.erasure_grace_days' => 0]);

        $user = User::factory()->create();
        $user->forceFill(['erasure_requested_at' => CarbonImmutable::now()->subMinute()])->save();

        $this->artisan('users:process-erasure-requests')
            ->expectsOutputToContain('1 erased')
            ->assertExitCode(0);
    }
}
