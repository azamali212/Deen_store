<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserSocialAccount;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakesGoogleLogin;
use Tests\TestCase;

final class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;
    use FakesGoogleLogin;

    private const ENDPOINT = '/api/v1/customer/auth/login/google';

    protected function setUp(): void
    {
        parent::setUp();

        // loginWithGoogle() assigns the 'customer' role to new accounts,
        // and PanelAccessService checks the real 'panel.customer.access'
        // permission that role carries — so tests need the actual
        // role/permission set the app seeds in production, not a bare
        // role name with nothing attached to it.
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_a_new_user_is_created_and_logged_in_via_google(): void
    {
        $profile = $this->fakeGoogleLogin([
            'email' => 'new.customer@example.com',
            'name' => 'Naya Customer',
        ]);

        $response = $this->postJson(self::ENDPOINT, ['id_token' => 'fake-token']);

        $response->assertOk();
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.user.email', 'new.customer@example.com');
        $this->assertNotEmpty($response->json('data.token'));

        $this->assertDatabaseHas('users', [
            'email' => 'new.customer@example.com',
        ]);

        $this->assertDatabaseHas('user_social_accounts', [
            'provider' => 'google',
            'provider_user_id' => $profile->googleId,
            'email' => 'new.customer@example.com',
        ]);
    }

    public function test_a_user_who_already_linked_google_logs_in_without_creating_a_duplicate_account(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $user->assignRole('customer');

        UserSocialAccount::factory()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-sub-12345',
        ]);

        $this->fakeGoogleLogin([
            'google_id' => 'google-sub-12345',
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson(self::ENDPOINT, ['id_token' => 'fake-token']);

        $response->assertOk();
        $response->assertJsonPath('data.user.email', 'existing@example.com');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_an_existing_password_account_is_auto_linked_by_matching_email(): void
    {
        $user = User::factory()->create(['email' => 'was.password.only@example.com']);
        $user->assignRole('customer');

        $this->fakeGoogleLogin([
            'email' => 'was.password.only@example.com',
        ]);

        $response = $this->postJson(self::ENDPOINT, ['id_token' => 'fake-token']);

        $response->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('user_social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
        ]);
    }

    public function test_an_invalid_google_token_is_rejected(): void
    {
        $this->fakeInvalidGoogleToken();

        $response = $this->postJson(self::ENDPOINT, ['id_token' => 'not-a-real-token']);

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
    }

    public function test_id_token_is_required(): void
    {
        $response = $this->postJson(self::ENDPOINT, []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['id_token']);
    }

    public function test_a_user_with_two_factor_enabled_is_prompted_for_two_factor_instead_of_being_logged_in(): void
    {
        $user = User::factory()->create([
            'email' => 'has2fa@example.com',
            'two_factor_enabled' => true,
        ]);
        $user->assignRole('customer');

        $this->fakeGoogleLogin(['email' => 'has2fa@example.com']);

        $response = $this->postJson(self::ENDPOINT, ['id_token' => 'fake-token']);

        $response->assertOk();
        $response->assertJsonPath('data.requires_two_factor', true);
        // No session token was issued — the flow stopped short of login.
        $this->assertEmpty($response->json('data.token'));
    }

    public function test_a_suspended_users_google_login_is_rejected(): void
    {
        $user = User::factory()->suspended()->create([
            'email' => 'suspended@example.com',
        ]);
        $user->assignRole('customer');

        $this->fakeGoogleLogin(['email' => 'suspended@example.com']);

        $response = $this->postJson(self::ENDPOINT, ['id_token' => 'fake-token']);

        $response->assertStatus(403);
    }
}
