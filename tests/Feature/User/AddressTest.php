<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'home',
            'is_default' => true,
            'recipient_name' => 'Azam Ali',
            'phone' => '+923001234567',
            'address_line_1' => 'Street 12, Block A',
            'city' => 'Karachi',
            'country_code' => 'PK',
        ], $overrides);
    }

    public function test_guest_cannot_list_addresses(): void
    {
        $response = $this->getJson('/api/v1/addresses');

        $response->assertStatus(401);
    }

    public function test_user_can_add_an_address(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/addresses', $this->validPayload());

        $response->assertCreated();
        $response->assertJsonPath('data.city', 'Karachi');

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'city' => 'Karachi',
            'is_default' => true,
        ]);
    }

    public function test_adding_an_eleventh_address_is_rejected(): void
    {
        $user = User::factory()->create();
        UserAddress::factory()->count(10)->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/addresses', $this->validPayload(['is_default' => false]));

        $response->assertStatus(422);

        // Still exactly 10 — the 11th never got in.
        $this->assertDatabaseCount('user_addresses', 10);
    }

    public function test_user_cannot_update_another_users_address(): void
    {
        $owner = User::factory()->create();
        $address = UserAddress::factory()->for($owner)->create();

        $attacker = User::factory()->create();
        Sanctum::actingAs($attacker);

        $response = $this->putJson(
            "/api/v1/addresses/{$address->id}",
            $this->validPayload(['city' => 'Hacked City']),
        );

        $response->assertStatus(404);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'city' => $address->city, // unchanged
        ]);
    }

    public function test_deleting_the_default_address_promotes_another_to_default(): void
    {
        $user = User::factory()->create();
        $default = UserAddress::factory()->default()->for($user)->create();
        $other = UserAddress::factory()->for($user)->create(['is_default' => false]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/addresses/{$default->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('user_addresses', ['id' => $default->id]);
        $this->assertDatabaseHas('user_addresses', ['id' => $other->id, 'is_default' => true]);
    }

    public function test_setting_a_new_default_unsets_the_previous_one(): void
    {
        $user = User::factory()->create();
        $oldDefault = UserAddress::factory()->default()->for($user)->create();
        $newDefault = UserAddress::factory()->for($user)->create(['is_default' => false]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/addresses/{$newDefault->id}/default");

        $response->assertOk();

        $this->assertDatabaseHas('user_addresses', ['id' => $newDefault->id, 'is_default' => true]);
        $this->assertDatabaseHas('user_addresses', ['id' => $oldDefault->id, 'is_default' => false]);
    }
}
