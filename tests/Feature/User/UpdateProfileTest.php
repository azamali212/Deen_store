<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesGeminiModeration;
use Tests\TestCase;

final class UpdateProfileTest extends TestCase
{
    use FakesGeminiModeration;
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'clean_user_1',
            'bio' => 'A perfectly normal bio.',
            'website_url' => 'https://example.com',
            'occupation' => 'Software Engineer',
            'company_name' => 'Acme Inc',
            'country_code' => 'PK',
            'timezone' => 'Asia/Karachi',
            'locale' => 'en',
            'profile_visibility' => 'public',
        ], $overrides);
    }

    public function test_authenticated_user_can_update_profile_with_clean_content(): void
    {
        $this->fakeGeminiClean();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', $this->validPayload());

        // 201, not 200 — this is the user's FIRST profile, so Laravel's
        // resource response sees a freshly-created model and returns 201.
        $response->assertCreated();
        $response->assertJsonPath('data.username', 'clean_user_1');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'username' => 'clean_user_1',
        ]);

        // The moderation check ran (proves it's actually wired in, not skipped).
        Http::assertSentCount(1);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.profile.updated',
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_profile_update_is_blocked_when_ai_flags_content(): void
    {
        $this->fakeGeminiFlagged([
            'bio' => ['category' => 'profanity_and_sexual_content', 'reason' => 'Explicit language.'],
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', $this->validPayload([
            'bio' => 'Some explicit content here.',
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('flagged_fields.bio.category', 'profanity_and_sexual_content');

        // Nothing was ever saved — the whole point of blocking.
        $this->assertDatabaseMissing('user_profiles', [
            'user_id' => $user->id,
        ]);

        // But it IS recorded, auto-rejected, for the admin trail.
        $this->assertDatabaseHas('profile_moderation_flags', [
            'user_id' => $user->id,
            'status' => 'rejected',
            'reviewed_by' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'moderation.profile.content_blocked',
            'subject_id' => (string) $user->id,
            'status' => 'denied',
        ]);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/v1/profile', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_inactive_user_cannot_update_profile(): void
    {
        $user = User::factory()->suspended()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', $this->validPayload());

        $response->assertStatus(403);
    }

    public function test_username_must_be_unique_across_profiles(): void
    {
        $this->fakeGeminiClean();

        $existingProfile = UserProfile::factory()->create(['username' => 'taken_name']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/profile', $this->validPayload([
            'username' => 'taken_name',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    }
}
