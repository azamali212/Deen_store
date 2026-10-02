<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Domain\User\Enums\ConsentType;
use App\Domain\User\Services\ConsentService;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * A5 — the consent ledger (BLUEPRINT section 18, P12-5).
 */
final class ConsentTest extends TestCase
{
    use RefreshDatabase;

    private const CONSENTS = '/api/v1/privacy/consents';

    public function test_guest_is_rejected(): void
    {
        $this->getJson(self::CONSENTS)->assertStatus(401);
    }

    public function test_index_lists_every_type_even_those_never_answered(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson(self::CONSENTS)->assertOk();

        $types = array_column($response->json('data'), 'type');

        $this->assertEqualsCanonicalizing(ConsentType::values(), $types);

        foreach ($response->json('data') as $row) {
            $this->assertFalse($row['granted'], $row['type'].' should start ungranted');
            $this->assertNull($row['version']);
        }
    }

    public function test_granting_writes_a_row_with_the_server_side_version(): void
    {
        config(['privacy.consent_versions.marketing' => '2026-09-01']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::CONSENTS, [
            'type' => ConsentType::MARKETING->value,
            // A client trying to claim an older version it never showed.
            'version' => '1999-01-01',
        ])->assertStatus(201);

        $this->assertDatabaseHas('user_consents', [
            'user_id' => $user->id,
            'type' => ConsentType::MARKETING->value,
            'version' => '2026-09-01',
            'withdrawn_at' => null,
            'superseded_at' => null,
        ]);
    }

    public function test_granting_the_same_version_twice_does_not_duplicate_rows(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::CONSENTS, ['type' => ConsentType::MARKETING->value])->assertStatus(201);
        $this->postJson(self::CONSENTS, ['type' => ConsentType::MARKETING->value])->assertStatus(201);

        $this->assertSame(1, UserConsent::query()->where('user_id', $user->id)->count());
    }

    public function test_a_new_version_supersedes_the_old_row_rather_than_withdrawing_it(): void
    {
        config(['privacy.consent_versions.privacy_policy' => '2026-09-01']);

        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $first = $service->grant($user, ConsentType::PRIVACY_POLICY);

        config(['privacy.consent_versions.privacy_policy' => '2027-01-15']);

        $second = $service->grant($user, ConsentType::PRIVACY_POLICY);

        $this->assertNotSame($first->id, $second->id);

        // The old row is closed as superseded — the user never said no.
        $this->assertNotNull($first->fresh()->superseded_at);
        $this->assertNull($first->fresh()->withdrawn_at);

        $this->assertSame('2027-01-15', $second->version);
        $this->assertTrue($second->isActive());

        $this->assertSame(2, UserConsent::query()->where('user_id', $user->id)->count());
    }

    public function test_index_flags_a_consent_whose_wording_has_since_changed(): void
    {
        config(['privacy.consent_versions.marketing' => '2026-09-01']);

        $user = User::factory()->create();
        app(ConsentService::class)->grant($user, ConsentType::MARKETING);

        config(['privacy.consent_versions.marketing' => '2027-06-01']);

        Sanctum::actingAs($user);
        $rows = collect($this->getJson(self::CONSENTS)->assertOk()->json('data'))
            ->keyBy('type');

        $this->assertTrue($rows['marketing']['granted']);
        $this->assertTrue($rows['marketing']['needs_reconsent']);
        $this->assertSame('2026-09-01', $rows['marketing']['version']);
        $this->assertSame('2027-06-01', $rows['marketing']['current_version']);
    }

    public function test_withdrawing_marketing_stamps_the_row_and_never_deletes_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson(self::CONSENTS, ['type' => ConsentType::MARKETING->value])->assertStatus(201);

        $this->deleteJson(self::CONSENTS.'/'.ConsentType::MARKETING->value)->assertOk();

        $row = UserConsent::query()->where('user_id', $user->id)->sole();

        $this->assertNotNull($row->withdrawn_at);
        $this->assertFalse($row->isActive());
    }

    public function test_terms_cannot_be_withdrawn_while_the_account_exists(): void
    {
        $user = User::factory()->create();
        app(ConsentService::class)->grant($user, ConsentType::TERMS);

        Sanctum::actingAs($user);

        $this->deleteJson(self::CONSENTS.'/'.ConsentType::TERMS->value)
            ->assertStatus(422)
            ->assertJsonPath('errors.type.0', fn (string $message): bool => str_contains($message, 'Deleting your account'));

        $this->assertTrue(
            app(ConsentService::class)->activeFor($user, ConsentType::TERMS)?->isActive() ?? false,
        );
    }

    public function test_withdrawing_something_never_granted_is_not_an_error(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson(self::CONSENTS.'/'.ConsentType::MARKETING->value)->assertOk();
    }

    public function test_unknown_consent_type_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(self::CONSENTS, ['type' => 'sell_my_data'])->assertStatus(422);
        $this->deleteJson(self::CONSENTS.'/sell_my_data')->assertStatus(422);
    }

    public function test_the_ledger_records_where_the_consent_came_from(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->withHeader('User-Agent', 'ZimalApp/1.0 (integration test)')
            ->postJson(self::CONSENTS, ['type' => ConsentType::MARKETING->value])
            ->assertStatus(201);

        $row = UserConsent::query()->where('user_id', $user->id)->sole();

        $this->assertNotNull($row->ip_address);
        $this->assertStringContainsString('ZimalApp/1.0', (string) $row->user_agent);
        $this->assertNotNull($row->granted_at);
    }

    public function test_history_keeps_every_row_in_reverse_order(): void
    {
        config(['privacy.consent_versions.marketing' => 'v1']);

        $user = User::factory()->create();
        $service = app(ConsentService::class);

        $service->grant($user, ConsentType::MARKETING);
        $service->withdraw($user, ConsentType::MARKETING);

        config(['privacy.consent_versions.marketing' => 'v2']);
        $service->grant($user, ConsentType::MARKETING);

        $history = $service->history($user);

        // Two rows, not three: the withdrawal CLOSED the v1 row rather than
        // adding one. Only a fresh grant inserts.
        $this->assertCount(2, $history);
        $this->assertSame('v2', $history->first()->version);
        $this->assertNotNull($history->last()->withdrawn_at);
    }
}
