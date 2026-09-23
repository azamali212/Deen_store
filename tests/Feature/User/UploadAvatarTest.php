<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesGeminiModeration;
use Tests\TestCase;

final class UploadAvatarTest extends TestCase
{
    use FakesGeminiModeration;
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_a_clean_avatar(): void
    {
        Storage::fake('public');
        $this->fakeGeminiClean();

        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['avatar_path' => null]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertOk();

        $profile = $user->profile()->first();
        $this->assertNotNull($profile->avatar_path);

        // The file really landed on the (faked) public disk.
        Storage::disk('public')->assertExists($profile->avatar_path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.avatar.uploaded',
            'subject_id' => (string) $user->id,
        ]);
    }

    public function test_avatar_upload_is_blocked_when_ai_flags_the_image(): void
    {
        Storage::fake('public');
        $this->fakeGeminiFlagged([
            'avatar' => ['category' => 'nudity', 'reason' => 'Explicit imagery detected.'],
        ], severity: 'high');

        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create(['avatar_path' => null]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('flagged_fields.avatar.category', 'nudity');

        // Nothing was ever written to disk — the check runs on the raw
        // uploaded bytes BEFORE AvatarService ever touches storage.
        $this->assertEmpty(Storage::disk('public')->allFiles('avatars'));

        $profile = $user->profile()->first();
        $this->assertNull($profile->avatar_path);

        $this->assertDatabaseHas('profile_moderation_flags', [
            'user_id' => $user->id,
            'status' => 'rejected',
            'severity' => 'high',
        ]);
    }

    public function test_guest_cannot_upload_avatar(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_non_image_file_is_rejected_by_validation(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->for($user)->create();
        Sanctum::actingAs($user);
        $this->fakeGeminiClean();

        $file = UploadedFile::fake()->create('resume.pdf', 100);

        $response = $this->postJson('/api/v1/profile/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar']);
    }
}
