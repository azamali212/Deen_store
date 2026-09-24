<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Notifications\SellerStoreNameChangeRequestedNotification;
use App\Domain\Seller\Notifications\SellerStoreNameChangeReviewedNotification;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 9b — renaming a live store (BLUEPRINT section 14b).
 */
final class SellerStoreRenameTest extends TestCase
{
    use RefreshDatabase;

    private const PROFILE = '/api/v1/seller/profile';

    private const ADMIN = '/api/v1/admin/sellers';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
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

    private function ask(string $name = 'Zimal Threads')
    {
        return $this->postJson(self::PROFILE.'/name', ['store_name' => $name]);
    }

    // ==================================================================
    // Requesting
    // ==================================================================

    public function test_the_owner_can_ask_for_a_new_name(): void
    {
        $reviewer = $this->admin();
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $response = $this->ask();

        $response->assertOk();
        $response->assertJsonPath('data.pending_name.requested', 'Zimal Threads');

        // C45 — the LIVE name has not moved.
        $response->assertJsonPath('data.store_name', $store->store_name);
        $this->assertSame($store->store_name, $store->refresh()->store_name);

        Notification::assertSentTo($reviewer, SellerStoreNameChangeRequestedNotification::class);
    }

    public function test_asking_twice_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->ask()->assertOk();
        $this->ask('Another Name')->assertStatus(409);
    }

    public function test_the_request_can_be_withdrawn_and_then_re_made(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->ask()->assertOk();
        $this->deleteJson(self::PROFILE.'/name')->assertOk();

        $this->assertNull($store->refresh()->pending_store_name);

        $this->ask('A Third Name')->assertOk();
    }

    public function test_withdrawing_with_nothing_pending_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->deleteJson(self::PROFILE.'/name')->assertStatus(409);
    }

    public function test_asking_for_the_name_you_already_have_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->ask($store->store_name)->assertStatus(409);
    }

    public function test_a_name_another_store_already_uses_is_refused(): void
    {
        $taken = $this->store();
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->ask($taken->store_name)->assertStatus(422);
    }

    /** P9-5 — the shop's name is the business's identity. */
    public function test_only_the_owner_can_ask(): void
    {
        $store = $this->store();
        $manager = User::factory()->create(['email' => 'manager@example.com']);
        $manager->assignRole('customer');
        $manager->assignRole('seller_manager');
        SellerTeamMember::factory()->role(SellerTeamRole::MANAGER)->create([
            'seller_profile_id' => $store->id,
            'user_id' => $manager->id,
            'invited_by' => $store->user_id,
        ]);

        Sanctum::actingAs($manager);

        $this->ask()->assertStatus(403);
    }

    public function test_a_closed_store_cannot_be_renamed(): void
    {
        $store = $this->store([
            'status' => SellerProfileStatus::CLOSED->value,
            'closed_at' => now(),
        ]);
        Sanctum::actingAs($store->user);

        $this->ask()->assertStatus(403);
    }

    // ==================================================================
    // Admin review
    // ==================================================================

    public function test_the_admin_queue_shows_who_is_waiting(): void
    {
        $waiting = $this->store();
        $this->store();

        Sanctum::actingAs($waiting->user);
        $this->ask()->assertOk();

        Sanctum::actingAs($this->admin());

        $response = $this->getJson(self::ADMIN.'?name_pending=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.pending_name.requested', 'Zimal Threads');
    }

    public function test_approving_renames_the_store(): void
    {
        $store = $this->store();
        $previous = $store->store_name;
        Sanctum::actingAs($store->user);
        $this->ask()->assertOk();

        Sanctum::actingAs($this->admin());
        $this->postJson(self::ADMIN."/{$store->id}/name/review", ['decision' => 'approve'])
            ->assertOk()
            ->assertJsonPath('data.store_name', 'Zimal Threads');

        $store->refresh();
        $this->assertSame('Zimal Threads', $store->store_name);
        $this->assertNull($store->pending_store_name);
        $this->assertNull($store->store_name_requested_at);
        $this->assertNotSame($previous, $store->store_name);

        Notification::assertSentTo($store->user, SellerStoreNameChangeReviewedNotification::class);
    }

    public function test_rejecting_needs_a_reason_and_leaves_the_name_alone(): void
    {
        $store = $this->store();
        $previous = $store->store_name;
        Sanctum::actingAs($store->user);
        $this->ask()->assertOk();

        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$store->id}/name/review", ['decision' => 'reject'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->postJson(self::ADMIN."/{$store->id}/name/review", [
            'decision' => 'reject',
            'reason' => 'That name impersonates another brand.',
        ])->assertOk();

        $store->refresh();
        $this->assertSame($previous, $store->store_name);
        $this->assertNull($store->pending_store_name);
    }

    /**
     * C44 — the race the second check exists for. Another store takes the
     * name while this request sits in the queue.
     */
    public function test_a_name_taken_after_the_request_is_caught_at_approval(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->ask('Zimal Threads')->assertOk();

        // Meanwhile, somebody else ends up with that exact name.
        $rival = $this->store();
        $rival->forceFill(['store_name' => 'Zimal Threads'])->save();

        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$store->id}/name/review", ['decision' => 'approve'])
            ->assertStatus(422);

        // The request is still pending, and nothing was renamed.
        $store->refresh();
        $this->assertSame('Zimal Threads', $store->pending_store_name);
        $this->assertNotSame('Zimal Threads', $store->store_name);
    }

    public function test_reviewing_with_nothing_pending_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($this->admin());

        $this->postJson(self::ADMIN."/{$store->id}/name/review", ['decision' => 'approve'])
            ->assertStatus(409);
    }

    public function test_an_admin_cannot_approve_their_own_stores_rename(): void
    {
        $admin = $this->admin();
        $admin->assignRole('customer');
        $admin->assignRole('seller');
        $store = SellerProfile::factory()->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);
        $this->ask()->assertOk();

        $this->postJson(self::ADMIN."/{$store->id}/name/review", ['decision' => 'approve'])
            ->assertStatus(403);
    }

    public function test_a_customer_cannot_review_a_rename(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);
        $this->ask()->assertOk();

        $this->postJson(self::ADMIN."/{$store->id}/name/review", ['decision' => 'approve'])
            ->assertStatus(403);
    }

    /** The admin filter had `in:active,suspended` typed out by hand. */
    public function test_closed_stores_can_be_filtered_for(): void
    {
        $this->store();
        $this->store(['status' => SellerProfileStatus::CLOSED->value, 'closed_at' => now()]);

        Sanctum::actingAs($this->admin());

        $this->getJson(self::ADMIN.'?status=closed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'closed');
    }
}
