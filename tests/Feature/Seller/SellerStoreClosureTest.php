<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Notifications\SellerStoreClosedNotification;
use App\Domain\Seller\Notifications\SellerStoreReopenRequestedNotification;
use App\Domain\Seller\Notifications\SellerStoreReopenedNotification;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesDocumentVerification;
use Tests\Support\FakesGeminiModeration;
use Tests\TestCase;

/**
 * Phase 8b — the seller closes their own store (BLUEPRINT section 13b).
 */
final class SellerStoreClosureTest extends TestCase
{
    use RefreshDatabase;
    use FakesDocumentVerification;
    use FakesGeminiModeration;

    private const PROFILE = '/api/v1/seller/profile';

    private const ADMIN = '/api/v1/admin/sellers';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
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

    private function addMember(SellerProfile $store, string $email, SellerTeamRole $role): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('customer');
        $user->assignRole($role->systemRole()->value);

        SellerTeamMember::factory()->role($role)->create([
            'seller_profile_id' => $store->id,
            'user_id' => $user->id,
            'invited_by' => $store->user_id,
        ]);

        return $user;
    }

    private function close(SellerProfile $store, ?string $name = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(self::PROFILE.'/close', [
            'confirm_store_name' => $name ?? $store->store_name,
            'reason' => 'Moving to a physical shop.',
        ]);
    }

    // ==================================================================
    // Closing
    // ==================================================================

    public function test_the_owner_can_close_their_store(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->close($store)
            ->assertOk()
            ->assertJsonPath('data.status', SellerProfileStatus::CLOSED->value)
            ->assertJsonPath('data.closure.reason', 'Moving to a physical shop.');

        $store->refresh();
        $this->assertTrue($store->isClosed());
        $this->assertNotNull($store->closed_at);

        Notification::assertSentTo($store->user, SellerStoreClosedNotification::class);
    }

    /** C34 — closing a business is not a one-click action. */
    public function test_the_store_name_must_be_typed_back_exactly(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->close($store, 'some other name')
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirm_store_name');

        $this->assertFalse($store->refresh()->isClosed());
    }

    public function test_the_confirmation_field_is_required(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->postJson(self::PROFILE.'/close', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('confirm_store_name');
    }

    /** C30 — closing must never be the escape hatch from an investigation. */
    public function test_a_suspended_store_cannot_be_closed_by_its_seller(): void
    {
        $store = $this->store([
            'status' => SellerProfileStatus::SUSPENDED->value,
            'suspended_at' => now(),
            'suspension_reason' => 'Under investigation.',
        ]);
        Sanctum::actingAs($store->user);

        $this->close($store)->assertStatus(403);

        $this->assertSame(SellerProfileStatus::SUSPENDED, $store->refresh()->status);
    }

    public function test_only_the_owner_can_close_the_store(): void
    {
        $store = $this->store();
        $manager = $this->addMember($store, 'manager@example.com', SellerTeamRole::MANAGER);
        Sanctum::actingAs($manager);

        $this->close($store)->assertStatus(403);

        $this->assertFalse($store->refresh()->isClosed());
    }

    public function test_closing_twice_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->close($store)->assertOk();
        $this->close($store)->assertStatus(403);
    }

    // ==================================================================
    // What closure does to access
    // ==================================================================

    /** C31 — rows stay, only access goes. C39 — the owner keeps theirs. */
    public function test_the_team_loses_access_but_their_rows_are_kept(): void
    {
        $store = $this->store();
        $manager = $this->addMember($store, 'manager@example.com', SellerTeamRole::MANAGER);
        $staff = $this->addMember($store, 'staff@example.com', SellerTeamRole::STAFF);

        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        $this->assertFalse($manager->fresh()->hasRole('seller_manager'));
        $this->assertFalse($staff->fresh()->hasRole('seller_staff'));

        // C39 — the owner keeps theirs, or they could not reach the route
        // that asks for the store back.
        $this->assertTrue($store->user->fresh()->hasRole('seller'));

        // C31 — nothing was deleted.
        $this->assertDatabaseCount('seller_team_members', 3);
        $this->assertDatabaseHas('seller_team_members', [
            'user_id' => $manager->id,
            'status' => 'active',
        ]);
    }

    public function test_a_closed_store_can_still_be_viewed_but_never_changed(): void
    {
        $this->fakeGeminiClean();
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        // Read: still fine, so the seller can see what happened.
        $this->getJson(self::PROFILE)
            ->assertOk()
            ->assertJsonPath('data.status', SellerProfileStatus::CLOSED->value);

        // C33 — every write guard picked up the new state from one helper.
        $this->putJson(self::PROFILE, ['description' => 'New text'])->assertStatus(403);
        $this->deleteJson(self::PROFILE.'/logo')->assertStatus(403);
    }

    public function test_nobody_can_be_invited_to_a_closed_store(): void
    {
        $store = $this->store();
        $this->addMember($store, 'manager@example.com', SellerTeamRole::MANAGER);
        User::factory()->create(['email' => 'newbie@example.com'])->assignRole('customer');

        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        $this->postJson('/api/v1/seller/team', ['email' => 'newbie@example.com', 'role' => 'staff'])
            ->assertStatus(403);
    }

    // ==================================================================
    // Reopening
    // ==================================================================

    public function test_the_owner_asks_and_an_admin_reopens(): void
    {
        $store = $this->store();
        $manager = $this->addMember($store, 'manager@example.com', SellerTeamRole::MANAGER);
        $reviewer = $this->admin();

        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        // The owner asks.
        $this->postJson(self::PROFILE.'/reopen')
            ->assertOk()
            ->assertJsonPath('data.closure.reopen_requested_at', fn ($v) => $v !== null);

        Notification::assertSentTo($reviewer, SellerStoreReopenRequestedNotification::class);

        // The admin grants it.
        Sanctum::actingAs($reviewer);
        $this->postJson(self::ADMIN."/{$store->id}/reopen")
            ->assertOk()
            ->assertJsonPath('data.status', SellerProfileStatus::ACTIVE->value);

        $store->refresh();
        $this->assertSame(SellerProfileStatus::ACTIVE, $store->status);
        $this->assertNull($store->closed_at);
        $this->assertNull($store->closure_reason);
        $this->assertNull($store->reopen_requested_at);

        // C31 — the same team comes straight back, with the same roles.
        $this->assertTrue($manager->fresh()->hasRole('seller_manager'));

        Notification::assertSentTo($store->user, SellerStoreReopenedNotification::class);
    }

    public function test_asking_twice_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        $this->postJson(self::PROFILE.'/reopen')->assertOk();
        $this->postJson(self::PROFILE.'/reopen')->assertStatus(409);
    }

    public function test_an_open_store_cannot_ask_to_be_reopened(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->postJson(self::PROFILE.'/reopen')->assertStatus(409);
    }

    public function test_an_admin_cannot_reopen_a_store_that_is_not_closed(): void
    {
        $store = $this->store();
        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$store->id}/reopen")->assertStatus(409);
    }

    public function test_an_admin_cannot_reopen_their_own_store(): void
    {
        $admin = $this->admin();
        $admin->assignRole('customer');
        $admin->assignRole('seller');
        $store = SellerProfile::factory()->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);
        $this->close($store)->assertOk();

        $this->postJson(self::ADMIN."/{$store->id}/reopen")->assertStatus(403);
    }

    public function test_a_customer_cannot_reopen_a_store(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        $this->postJson(self::ADMIN."/{$store->id}/reopen")->assertStatus(403);
    }

    // ==================================================================
    // C40 — the two doors must stay apart
    // ==================================================================

    public function test_an_admin_cannot_reactivate_a_closed_store_through_the_suspension_door(): void
    {
        $store = $this->store();
        $manager = $this->addMember($store, 'manager@example.com', SellerTeamRole::MANAGER);

        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        Sanctum::actingAs($this->admin());

        // Before C40 this passed the `=== ACTIVE` check and flipped the
        // store to active with closed_at still set and the team's roles
        // never restored.
        $this->postJson(self::ADMIN."/{$store->id}/reactivate")->assertStatus(409);

        $store->refresh();
        $this->assertTrue($store->isClosed());
        $this->assertFalse($manager->fresh()->hasRole('seller_manager'));
    }

    public function test_an_admin_cannot_suspend_a_closed_store(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->close($store)->assertOk();

        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$store->id}/suspend", ['reason' => 'Fraud found later.'])
            ->assertStatus(409);

        // The record of who ended this business stays intact.
        $this->assertTrue($store->refresh()->isClosed());
    }
}
