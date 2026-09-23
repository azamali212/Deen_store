<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_data(): void
    {
        $response = $this->getJson('/api/v1/privacy/data-export');

        $response->assertStatus(401);
    }

    public function test_user_can_download_their_own_data_export(): void
    {
        $user = User::factory()->create(['email' => 'azam@example.com']);
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'username' => 'azam_ali',
        ]);
        UserAddress::factory()->default()->create([
            'user_id' => $user->id,
            'city' => 'Karachi',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/privacy/data-export');

        $response->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            "zimal-data-export-{$user->id}-",
            $response->headers->get('content-disposition'),
        );

        $payload = json_decode($response->streamedContent(), true);

        $this->assertSame($user->id, $payload['account']['id']);
        $this->assertSame('azam@example.com', $payload['account']['email']);
        $this->assertSame('azam_ali', $payload['profile']['username']);
        $this->assertCount(1, $payload['addresses']);
        $this->assertSame('Karachi', $payload['addresses'][0]['city']);
        $this->assertArrayHasKey('preferences', $payload);

        // Never leaked, whatever else changes about the export shape.
        $this->assertArrayNotHasKey('password', $payload['account']);
        $this->assertArrayNotHasKey('two_factor_secret', $payload['account']);
    }

    public function test_export_works_for_a_user_with_no_profile_or_preferences_yet(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/privacy/data-export');

        $response->assertOk();

        $payload = json_decode($response->streamedContent(), true);

        $this->assertNull($payload['profile']);
        $this->assertSame([], $payload['addresses']);
    }

    public function test_exporting_data_writes_an_audit_log_entry(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/privacy/data-export');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.data.exported',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }
}
