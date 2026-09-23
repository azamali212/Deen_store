<?php

declare(strict_types=1);

namespace Tests\Feature\Moderation;

use App\Domain\Moderation\Enums\ModerationStatus;
use App\Domain\Permissions\Data\PermissionMap;
use App\Models\ModerationFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ModerationFlagAdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        Role::create(['name' => 'super_admin', 'guard_name' => PermissionMap::GUARD]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Sanctum::actingAs($admin);

        return $admin;
    }

    public function test_super_admin_can_list_pending_flags_by_default(): void
    {
        $this->actingAsSuperAdmin();

        ModerationFlag::factory()->count(2)->create();
        ModerationFlag::factory()->resolved()->create();

        $response = $this->getJson('/api/v1/admin/moderation/flags');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(2, 'data.data');
    }

    public function test_super_admin_can_see_all_flags_with_status_all(): void
    {
        $this->actingAsSuperAdmin();

        ModerationFlag::factory()->count(2)->create();
        ModerationFlag::factory()->resolved()->create();

        $response = $this->getJson('/api/v1/admin/moderation/flags?status=all');

        $response->assertOk();
        $response->assertJsonCount(3, 'data.data');
    }

    public function test_regular_user_cannot_access_moderation_flags(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/moderation/flags');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_moderation_flags(): void
    {
        $response = $this->getJson('/api/v1/admin/moderation/flags');

        $response->assertStatus(401);
    }

    public function test_super_admin_can_approve_a_pending_flag(): void
    {
        $admin = $this->actingAsSuperAdmin();

        $flag = ModerationFlag::factory()->create();

        $response = $this->patchJson("/api/v1/admin/moderation/flags/{$flag->id}/resolve", [
            'status' => 'approved',
            'notes' => 'Looks fine on manual review.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('profile_moderation_flags', [
            'id' => $flag->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        // Resolving fires ModerationFlagResolved -> its own audit trail entry.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'moderation.flag.resolved',
        ]);
    }

    public function test_resolving_an_already_resolved_flag_returns_conflict(): void
    {
        $this->actingAsSuperAdmin();

        $flag = ModerationFlag::factory()->resolved(ModerationStatus::APPROVED)->create();

        $response = $this->patchJson("/api/v1/admin/moderation/flags/{$flag->id}/resolve", [
            'status' => 'rejected',
        ]);

        $response->assertStatus(409);
    }

    public function test_resolve_rejects_an_invalid_status_value(): void
    {
        $this->actingAsSuperAdmin();

        $flag = ModerationFlag::factory()->create();

        $response = $this->patchJson("/api/v1/admin/moderation/flags/{$flag->id}/resolve", [
            'status' => 'maybe',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
