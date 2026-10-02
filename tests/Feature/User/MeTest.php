<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A1 — GET /v1/me: who am I and what may I do right now.
 */
final class MeTest extends TestCase
{
    use RefreshDatabase;

    private const ME = '/api/v1/me';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function store(array $overrides = []): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        return SellerProfile::factory()->create(['user_id' => $owner->id] + $overrides);
    }

    private function member(SellerProfile $store, string $email, SellerTeamRole $role): User
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

    public function test_guest_is_rejected(): void
    {
        $this->getJson(self::ME)->assertStatus(401);
    }

    public function test_a_plain_customer_has_no_seller_context(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');
        Sanctum::actingAs($user);

        $response = $this->getJson(self::ME);

        $response->assertOk();
        // null, not an empty object — this person is in no store, and that
        // is the honest answer.
        $response->assertJsonPath('data.seller', null);
        $response->assertJsonPath('data.accessible_panels', ['customer']);
        $response->assertJsonPath('data.user.email', $user->email);
    }

    public function test_the_owner_sees_their_store_and_every_power(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $response = $this->getJson(self::ME);

        $response->assertOk();
        $response->assertJsonPath('data.seller.role', 'owner');
        $response->assertJsonPath('data.seller.store_id', $store->id);
        $response->assertJsonPath('data.seller.store_name', $store->store_name);
        $response->assertJsonPath('data.seller.store_is_open', true);

        foreach (['manage_team', 'manage_bank_details', 'manage_kyc_documents',
            'rename_store', 'close_store', 'edit_store_profile'] as $ability) {
            $response->assertJsonPath("data.seller.can.{$ability}", true);
        }
    }

    /**
     * The whole reason this endpoint exists: roles and permissions alone
     * cannot tell an owner from a manager here, because both hold the same
     * `marketplace.stores` permission.
     */
    public function test_a_manager_may_edit_the_store_but_not_the_bank(): void
    {
        $store = $this->store();
        $manager = $this->member($store, 'manager@example.com', SellerTeamRole::MANAGER);
        Sanctum::actingAs($manager);

        $response = $this->getJson(self::ME);

        $response->assertOk();
        $response->assertJsonPath('data.seller.role', 'manager');
        $response->assertJsonPath('data.seller.store_id', $store->id);
        $response->assertJsonPath('data.seller.can.edit_store_profile', true);
        $response->assertJsonPath('data.seller.can.manage_bank_details', false);
        $response->assertJsonPath('data.seller.can.manage_team', false);
        $response->assertJsonPath('data.seller.can.close_store', false);
    }

    public function test_staff_may_do_none_of_the_store_powers(): void
    {
        $store = $this->store();
        $staff = $this->member($store, 'staff@example.com', SellerTeamRole::STAFF);
        Sanctum::actingAs($staff);

        $response = $this->getJson(self::ME);

        $response->assertOk();
        $response->assertJsonPath('data.seller.role', 'staff');
        $response->assertJsonPath('data.seller.can.edit_store_profile', false);
        $response->assertJsonPath('data.seller.can.manage_bank_details', false);
    }

    /** The frontend needs to know WHY a write is about to be refused. */
    public function test_a_suspended_store_reports_that_it_is_shut(): void
    {
        $store = $this->store([
            'status' => SellerProfileStatus::SUSPENDED->value,
            'suspended_at' => now(),
            'suspension_reason' => 'Under investigation.',
        ]);
        Sanctum::actingAs($store->user);

        $response = $this->getJson(self::ME);

        $response->assertOk();
        $response->assertJsonPath('data.seller.store_status', 'suspended');
        $response->assertJsonPath('data.seller.store_is_open', false);
        // The abilities still say "owner" — the store being shut is a
        // SEPARATE fact, and both have to be read together.
        $response->assertJsonPath('data.seller.can.manage_bank_details', true);
    }

    /**
     * The reason this is its own endpoint rather than part of the login
     * response: it has to survive a role change without a new login.
     */
    public function test_it_reflects_a_promotion_without_logging_in_again(): void
    {
        $store = $this->store();
        $staff = $this->member($store, 'staff@example.com', SellerTeamRole::STAFF);
        Sanctum::actingAs($staff);

        $this->getJson(self::ME)->assertJsonPath('data.seller.can.edit_store_profile', false);

        // The owner promotes them while they are still logged in.
        $member = SellerTeamMember::query()
            ->where('user_id', $staff->id)->firstOrFail();

        Sanctum::actingAs($store->user);
        $this->patchJson("/api/v1/seller/team/{$member->id}", ['role' => 'manager'])->assertOk();

        // Same token, no new login.
        Sanctum::actingAs($staff->fresh());
        $this->getJson(self::ME)
            ->assertJsonPath('data.seller.role', 'manager')
            ->assertJsonPath('data.seller.can.edit_store_profile', true);
    }

    public function test_a_removed_member_loses_their_seller_context(): void
    {
        $store = $this->store();
        $staff = $this->member($store, 'staff@example.com', SellerTeamRole::STAFF);
        $member = SellerTeamMember::query()->where('user_id', $staff->id)->firstOrFail();

        Sanctum::actingAs($store->user);
        $this->deleteJson("/api/v1/seller/team/{$member->id}")->assertOk();

        Sanctum::actingAs($staff->fresh());
        $this->getJson(self::ME)
            ->assertOk()
            ->assertJsonPath('data.seller', null);
    }

    // ==================================================================
    // Permissions actually arrive (they used to be silently empty)
    // ==================================================================

    /**
     * UserResource used getPermissionNames(), which is DIRECT permissions
     * only. This app attaches permissions to roles and never to a user, so
     * the list came back [] for everyone.
     */
    public function test_permissions_held_through_a_role_are_returned(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Sanctum::actingAs($admin);

        $response = $this->getJson(self::ME);

        $response->assertOk();
        $this->assertNotEmpty(
            $response->json('data.user.permissions'),
            'A super_admin holds every permission through their role; an empty list means only DIRECT permissions are being read.',
        );
    }

    /**
     * The concrete difference the frontend needs: both roles run the shop,
     * but only the owner can touch payouts.
     *
     * C69 — this test asserted 'payments.payouts', which is a MODULE key in
     * PermissionMap, not a permission. permissions() builds "module.action",
     * so what actually exists is payments.payouts.view / .approve / .manage.
     * The bare key is never stored, so that assertion could only ever fail.
     * The very next line in the same test already used the correct
     * module.action form (catalog.products.create) — the two halves of one
     * test disagreed about what a permission name looks like.
     */
    public function test_the_owner_has_payouts_and_the_manager_does_not(): void
    {
        $store = $this->store();
        $manager = $this->member($store, 'manager@example.com', SellerTeamRole::MANAGER);

        Sanctum::actingAs($store->user);
        $ownerPermissions = $this->getJson(self::ME)->json('data.user.permissions');

        $this->assertContains('payments.payouts.view', $ownerPermissions);
        $this->assertContains('payments.payouts.manage', $ownerPermissions);

        Sanctum::actingAs($manager);
        $managerPermissions = $this->getJson(self::ME)->json('data.user.permissions');

        // A prefix check, not one exact string. The point is that a manager
        // holds NO payout permission at all, so adding a fourth action to
        // the module later must not quietly slip past this test.
        $this->assertSame(
            [],
            array_values(array_filter(
                $managerPermissions,
                static fn (string $permission): bool => str_starts_with($permission, 'payments.payouts'),
            )),
            'A manager must hold no payout permission at all.',
        );

        // ...but they DO run the shop, so the list is real, not just empty.
        $this->assertContains('catalog.products.create', $managerPermissions);
    }
}
