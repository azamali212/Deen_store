<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Domain\Permissions\Data\PermissionMap;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AuditSummaryTest extends TestCase
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

    /**
     * AuditAiSummaryService reads plain text back from Gemini (not the
     * JSON-inside-text envelope the moderation feature uses) — so this
     * fake mirrors Gemini's real response shape for a plain generateContent
     * call, with our chosen sentence sitting where Gemini would put its
     * generated text.
     */
    private function fakeGeminiSummary(string $text): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'finishReason' => 'STOP',
                        'content' => [
                            'parts' => [
                                ['text' => $text],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_guest_cannot_get_ai_summary(): void
    {
        Http::fake();

        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/admin/audit/users/{$user->id}/summary");

        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_get_ai_summary(): void
    {
        Http::fake();

        $target = User::factory()->create();
        $plainUser = User::factory()->create();
        Sanctum::actingAs($plainUser);

        $response = $this->getJson("/api/v1/admin/audit/users/{$target->id}/summary");

        $response->assertStatus(403);
    }

    public function test_super_admin_gets_ai_summary_for_user_with_activity(): void
    {
        $this->actingAsSuperAdmin();

        $target = User::factory()->create();
        AuditLog::factory()->forSubject($target)->count(3)->create();

        $this->fakeGeminiSummary('User logged in twice and updated their profile once. Nothing unusual.');

        $response = $this->getJson("/api/v1/admin/audit/users/{$target->id}/summary");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.user_id', $target->id);
        $response->assertJsonPath('data.days', 30);
        $response->assertJsonPath(
            'data.summary',
            'User logged in twice and updated their profile once. Nothing unusual.',
        );
    }

    public function test_summary_is_404_when_user_has_no_audit_activity(): void
    {
        $this->actingAsSuperAdmin();

        $target = User::factory()->create();
        // Deliberately no AuditLog rows for $target.

        Http::fake();

        $response = $this->getJson("/api/v1/admin/audit/users/{$target->id}/summary");

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString(
            'no audit activity',
            $response->json('message'),
        );
    }

    public function test_summary_is_503_when_gemini_api_key_is_missing(): void
    {
        $this->actingAsSuperAdmin();

        $target = User::factory()->create();
        AuditLog::factory()->forSubject($target)->create();

        // Simulate a deployment where GEMINI_API_KEY was never set — the
        // service must check for this itself instead of trusting Gemini to
        // reject an empty key.
        config(['services.gemini.api_key' => null]);

        Http::fake();

        $response = $this->getJson("/api/v1/admin/audit/users/{$target->id}/summary");

        $response->assertStatus(503);
        $response->assertJsonPath('success', false);
    }

    public function test_days_query_parameter_is_clamped_to_a_maximum_of_90(): void
    {
        $this->actingAsSuperAdmin();

        $target = User::factory()->create();
        AuditLog::factory()->forSubject($target)->create();

        $this->fakeGeminiSummary('Summary text.');

        $response = $this->getJson("/api/v1/admin/audit/users/{$target->id}/summary?days=500");

        $response->assertOk();
        $response->assertJsonPath('data.days', 90);
    }

    public function test_days_query_parameter_is_clamped_to_a_minimum_of_1(): void
    {
        $this->actingAsSuperAdmin();

        $target = User::factory()->create();
        // Pinned to a few minutes ago (instead of the factory's random
        // up-to-24-hours-ago default) so this log is reliably inside the
        // clamped 1-day window no matter when the test actually runs.
        AuditLog::factory()->forSubject($target)->create([
            'occurred_at' => now()->subMinutes(5),
        ]);

        $this->fakeGeminiSummary('Summary text.');

        $response = $this->getJson("/api/v1/admin/audit/users/{$target->id}/summary?days=0");

        $response->assertOk();
        $response->assertJsonPath('data.days', 1);
    }
}
