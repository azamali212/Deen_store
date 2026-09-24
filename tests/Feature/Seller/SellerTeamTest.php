<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Notifications\SellerTeamAccessChangedNotification;
use App\Domain\Seller\Notifications\SellerTeamInvitationNotification;
use App\Domain\Seller\Notifications\SellerTeamMemberJoinedNotification;
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
 * Phase 7 — seller team (BLUEPRINT section 12).
 */
final class SellerTeamTest extends TestCase
{
    use RefreshDatabase;
    use FakesDocumentVerification;
    use FakesGeminiModeration;

    private const TEAM = '/api/v1/seller/team';

    private const INVITATIONS = '/api/v1/seller/invitations';

    private const PROFILE = '/api/v1/seller/profile';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    /** A store whose owner already has the owner membership (via the factory). */
    private function store(array $overrides = []): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        return SellerProfile::factory()->create(['user_id' => $owner->id] + $overrides);
    }

    private function customer(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('customer');

        return $user;
    }

    /** Invite + accept, so the person is a real active member. */
    private function addMember(SellerProfile $store, User $user, SellerTeamRole $role): SellerTeamMember
    {
        $member = SellerTeamMember::factory()->role($role)->create([
            'seller_profile_id' => $store->id,
            'user_id' => $user->id,
            'invited_by' => $store->user_id,
        ]);

        $user->assignRole($role->systemRole()->value);

        return $member;
    }

    // ==================================================================
    // The core fix: a member can reach the store at all
    // ==================================================================

    public function test_a_staff_member_can_open_the_store_that_is_not_theirs(): void
    {
        $store = $this->store(['store_name' => 'Zimal Fabrics']);
        $staff = $this->customer('staff@example.com');
        $this->addMember($store, $staff, SellerTeamRole::STAFF);

        Sanctum::actingAs($staff);

        // Before Phase 7 this was a 404: the store was found through
        // seller_profiles.user_id, which only ever knew the owner.
        $this->getJson(self::PROFILE)
            ->assertOk()
            ->assertJsonPath('data.store_name', 'Zimal Fabrics');
    }

    public function test_a_seller_with_no_store_still_gets_404(): void
    {
        $stranger = $this->customer('nobody@example.com');
        $stranger->assignRole('seller');
        Sanctum::actingAs($stranger);

        $this->getJson(self::PROFILE)->assertStatus(404);
    }

    // ==================================================================
    // What each role may do
    // ==================================================================

    public function test_a_manager_can_edit_the_storefront_but_not_the_bank(): void
    {
        $this->fakeDocumentVerifier();
        // The manager's edit really does reach moderation — it is a public
        // storefront field, so it must be faked like anywhere else.
        $this->fakeGeminiClean();
        $store = $this->store();
        $manager = $this->customer('manager@example.com');
        $this->addMember($store, $manager, SellerTeamRole::MANAGER);
        Sanctum::actingAs($manager);

        $this->putJson(self::PROFILE, ['description' => 'Updated by the manager'])->assertOk();

        // payments.payouts is the owner's alone.
        $this->putJson(self::PROFILE, [
            'bank_account_title' => 'Manager Grab',
            'bank_name' => 'HBL',
            'bank_account_number' => '11112222333344',
        ])->assertStatus(403);

        $this->assertDatabaseHas('seller_profiles', [
            'id' => $store->id,
            'description' => 'Updated by the manager',
        ]);
    }

    public function test_staff_can_only_read_the_store_profile(): void
    {
        $this->fakeDocumentVerifier();
        $store = $this->store();
        $staff = $this->customer('staff@example.com');
        $this->addMember($store, $staff, SellerTeamRole::STAFF);
        Sanctum::actingAs($staff);

        $this->getJson(self::PROFILE)->assertOk();
        $this->putJson(self::PROFILE, ['description' => 'Staff trying to edit'])->assertStatus(403);
        $this->deleteJson(self::PROFILE.'/logo')->assertStatus(403);
    }

    public function test_only_the_owner_can_manage_the_team(): void
    {
        $store = $this->store();
        $manager = $this->customer('manager@example.com');
        $this->addMember($store, $manager, SellerTeamRole::MANAGER);
        $this->customer('newbie@example.com');
        Sanctum::actingAs($manager);

        // A manager can SEE the team...
        $this->getJson(self::TEAM)->assertOk();

        // ...but cannot grow it (P7-3).
        $this->postJson(self::TEAM, ['email' => 'newbie@example.com', 'role' => 'staff'])
            ->assertStatus(403);
    }

    // ==================================================================
    // Invite -> accept
    // ==================================================================

    public function test_owner_invites_an_existing_account_and_they_accept(): void
    {
        Notification::fake();
        $store = $this->store(['store_name' => 'Zimal Fabrics']);
        $invitee = $this->customer('newbie@example.com');
        Sanctum::actingAs($store->user);

        $invite = $this->postJson(self::TEAM, ['email' => 'newbie@example.com', 'role' => 'staff']);

        $invite->assertCreated();
        $invite->assertJsonPath('data.status', 'invited');
        $invite->assertJsonPath('data.role', 'staff');
        $memberId = $invite->json('data.id');

        Notification::assertSentTo($invitee, SellerTeamInvitationNotification::class);
        // No access until they accept.
        $this->assertFalse($invitee->fresh()->hasRole('seller_staff'));

        // The invitee accepts — WITHOUT having any seller role yet (C21).
        Sanctum::actingAs($invitee);
        $this->getJson(self::INVITATIONS)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.store.store_name', 'Zimal Fabrics');

        $accept = $this->postJson(self::INVITATIONS."/{$memberId}/accept");

        $accept->assertOk();
        $accept->assertJsonPath('data.status', 'active');

        $invitee = $invitee->fresh();
        $this->assertTrue($invitee->hasRole('seller_staff'));
        $this->assertTrue($invitee->hasRole('customer'));   // C4 — still a shopper
        $this->assertTrue($invitee->can('panel.seller.access'));

        Notification::assertSentTo($store->user, SellerTeamMemberJoinedNotification::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.team.member_joined']);
    }

    public function test_an_invitation_can_be_declined(): void
    {
        $store = $this->store();
        $invitee = $this->customer('newbie@example.com');
        $member = SellerTeamMember::factory()->invited()->create([
            'seller_profile_id' => $store->id,
            'user_id' => $invitee->id,
        ]);
        Sanctum::actingAs($invitee);

        $this->postJson(self::INVITATIONS."/{$member->id}/decline")
            ->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $this->assertFalse($invitee->fresh()->hasRole('seller_staff'));
    }

    public function test_you_cannot_touch_someone_elses_invitation(): void
    {
        $store = $this->store();
        $invitee = $this->customer('newbie@example.com');
        $outsider = $this->customer('outsider@example.com');
        $member = SellerTeamMember::factory()->invited()->create([
            'seller_profile_id' => $store->id,
            'user_id' => $invitee->id,
        ]);
        Sanctum::actingAs($outsider);

        $this->postJson(self::INVITATIONS."/{$member->id}/accept")->assertStatus(404);
        $this->assertSame(
            SellerTeamMemberStatus::INVITED,
            $member->fresh()->status,
        );
    }

    public function test_an_invitation_cannot_be_accepted_twice(): void
    {
        $store = $this->store();
        $member = $this->addMember($store, $this->customer('staff@example.com'), SellerTeamRole::STAFF);
        Sanctum::actingAs($member->user);

        $this->postJson(self::INVITATIONS."/{$member->id}/accept")->assertStatus(409);
    }

    // ==================================================================
    // Invite guards
    // ==================================================================

    public function test_inviting_an_email_with_no_account_is_refused(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->postJson(self::TEAM, ['email' => 'ghost@example.com', 'role' => 'staff'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a_person_can_only_belong_to_one_store(): void
    {
        $storeA = $this->store();
        $storeB = $this->store();
        $shared = $this->customer('shared@example.com');
        $this->addMember($storeA, $shared, SellerTeamRole::STAFF);

        Sanctum::actingAs($storeB->user);

        $this->postJson(self::TEAM, ['email' => 'shared@example.com', 'role' => 'staff'])
            ->assertStatus(409);
    }

    public function test_the_owner_cannot_invite_themselves_or_create_a_second_owner(): void
    {
        $store = $this->store();
        Sanctum::actingAs($store->user);

        $this->postJson(self::TEAM, ['email' => $store->user->email, 'role' => 'staff'])
            ->assertStatus(422);

        $this->customer('newbie@example.com');
        $this->postJson(self::TEAM, ['email' => 'newbie@example.com', 'role' => 'owner'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    // ==================================================================
    // Role change / removal
    // ==================================================================

    public function test_owner_can_promote_staff_to_manager(): void
    {
        Notification::fake();
        $store = $this->store();
        $staff = $this->customer('staff@example.com');
        $member = $this->addMember($store, $staff, SellerTeamRole::STAFF);
        Sanctum::actingAs($store->user);

        $this->patchJson(self::TEAM."/{$member->id}", ['role' => 'manager'])
            ->assertOk()
            ->assertJsonPath('data.role', 'manager');

        $staff = $staff->fresh();
        $this->assertTrue($staff->hasRole('seller_manager'));
        $this->assertFalse($staff->hasRole('seller_staff'));

        Notification::assertSentTo($staff, SellerTeamAccessChangedNotification::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.team.role_changed']);
    }

    public function test_owner_can_remove_a_member_and_their_access_goes_away(): void
    {
        Notification::fake();
        $store = $this->store();
        $staff = $this->customer('staff@example.com');
        $member = $this->addMember($store, $staff, SellerTeamRole::STAFF);
        Sanctum::actingAs($store->user);

        $this->deleteJson(self::TEAM."/{$member->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'revoked');

        $staff = $staff->fresh();
        $this->assertFalse($staff->hasRole('seller_staff'));
        $this->assertTrue($staff->hasRole('customer'));   // account untouched

        // The row stays, so the audit trail still shows they had access.
        $this->assertDatabaseHas('seller_team_members', [
            'id' => $member->id,
            'status' => 'revoked',
        ]);

        Sanctum::actingAs($staff);
        $this->getJson(self::PROFILE)->assertStatus(403); // role gone -> middleware

        $this->assertDatabaseHas('audit_logs', ['action' => 'seller.team.member_removed']);
    }

    public function test_the_owner_cannot_demote_or_remove_themselves(): void
    {
        $store = $this->store();
        $ownerMember = SellerTeamMember::query()
            ->where('seller_profile_id', $store->id)
            ->where('user_id', $store->user_id)
            ->firstOrFail();
        Sanctum::actingAs($store->user);

        $this->patchJson(self::TEAM."/{$ownerMember->id}", ['role' => 'manager'])->assertStatus(403);
        $this->deleteJson(self::TEAM."/{$ownerMember->id}")->assertStatus(403);

        $this->assertTrue($ownerMember->fresh()->isOwner());
    }

    public function test_a_member_of_another_store_cannot_be_touched(): void
    {
        $storeA = $this->store();
        $storeB = $this->store();
        $member = $this->addMember($storeB, $this->customer('other@example.com'), SellerTeamRole::STAFF);
        Sanctum::actingAs($storeA->user);

        $this->deleteJson(self::TEAM."/{$member->id}")->assertStatus(404);
        $this->patchJson(self::TEAM."/{$member->id}", ['role' => 'manager'])->assertStatus(404);
    }

    // ==================================================================
    // Interaction with Phase 6 suspension (C24)
    // ==================================================================

    public function test_a_suspended_store_cannot_change_its_team(): void
    {
        $store = $this->store();
        $store->update(['status' => 'suspended', 'suspension_reason' => 'Fraud', 'suspended_at' => now()]);
        $this->customer('newbie@example.com');
        Sanctum::actingAs($store->user);

        $this->postJson(self::TEAM, ['email' => 'newbie@example.com', 'role' => 'staff'])
            ->assertStatus(403);
    }

    public function test_nobody_joins_a_suspended_store(): void
    {
        $store = $this->store();
        $invitee = $this->customer('newbie@example.com');
        $member = SellerTeamMember::factory()->invited()->create([
            'seller_profile_id' => $store->id,
            'user_id' => $invitee->id,
        ]);
        $store->update(['status' => 'suspended', 'suspension_reason' => 'Fraud', 'suspended_at' => now()]);
        Sanctum::actingAs($invitee);

        $this->postJson(self::INVITATIONS."/{$member->id}/accept")->assertStatus(403);
        $this->assertFalse($invitee->fresh()->hasRole('seller_staff'));
    }

    // ==================================================================
    // Listing
    // ==================================================================

    public function test_the_team_list_shows_the_owner_first_and_hides_nothing(): void
    {
        $store = $this->store();
        $this->addMember($store, $this->customer('m@example.com'), SellerTeamRole::MANAGER);
        $this->addMember($store, $this->customer('s@example.com'), SellerTeamRole::STAFF);
        Sanctum::actingAs($store->user);

        $response = $this->getJson(self::TEAM);

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.role', 'owner');
        $response->assertJsonPath('data.0.is_owner', true);
        $response->assertJsonPath('data.1.role', 'manager');
        $response->assertJsonPath('data.2.user.email', 's@example.com');
    }
}
