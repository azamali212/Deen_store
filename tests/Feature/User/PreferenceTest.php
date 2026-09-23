<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_null_data_when_no_preferences_set_yet(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/preferences');

        $response->assertOk();
        $response->assertJsonPath('data', null);
    }

    public function test_first_update_creates_preferences_with_sensible_defaults(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/preferences', [
            'theme' => 'dark',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.theme', 'dark');
        // Fields we never sent should fall back to DTO defaults, not null.
        $response->assertJsonPath('data.language', 'en');
        $response->assertJsonPath('data.currency', 'USD');
        $response->assertJsonPath('data.email_notifications', true);
        $response->assertJsonPath('data.sms_notifications', false);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'theme' => 'dark',
        ]);
    }

    public function test_second_update_modifies_the_same_row(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/preferences', ['theme' => 'dark']);
        $response = $this->putJson('/api/v1/preferences', ['theme' => 'light']);

        $response->assertOk(); // 200 this time — updating, not creating
        $response->assertJsonPath('data.theme', 'light');

        $this->assertDatabaseCount('user_preferences', 1);
    }

    public function test_guest_cannot_view_or_update_preferences(): void
    {
        $this->getJson('/api/v1/preferences')->assertStatus(401);
        $this->putJson('/api/v1/preferences', ['theme' => 'dark'])->assertStatus(401);
    }

    public function test_invalid_theme_value_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/preferences', ['theme' => 'rainbow']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['theme']);
    }
}
