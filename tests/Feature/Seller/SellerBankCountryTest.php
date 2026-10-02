<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FakesGeminiModeration;
use Tests\TestCase;

/**
 * A4 — bank accounts per country (BLUEPRINT section 17).
 */
final class SellerBankCountryTest extends TestCase
{
    use RefreshDatabase;
    use FakesGeminiModeration;

    private const BASE = '/api/v1/seller/profile';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->fakeGeminiClean();
    }

    private function seller(string $country = 'PK'): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        $profile = SellerProfile::factory()->create([
            'user_id' => $owner->id,
            'country' => $country,
        ]);

        Sanctum::actingAs($owner);

        return $profile;
    }

    /** @param array<string, mixed> $overrides */
    private function bank(array $overrides = []): array
    {
        return array_merge([
            'bank_account_title' => 'Zimal Fabrics Pvt Ltd',
            'bank_name' => 'Standard Chartered',
            'bank_account_number' => 'PK36SCBL0000001123456702',
        ], $overrides);
    }

    // ==================================================================
    // P11-3 — the checksum is the point
    // ==================================================================

    /**
     * The failure that actually costs money: ONE wrong digit. The IBAN
     * still has the right shape and the right length, so a regex waves it
     * through and the payout leaves for a stranger's account. mod-97 is
     * what catches it.
     */
    public function test_an_iban_with_one_mistyped_digit_is_refused(): void
    {
        $this->seller('PK');

        // PK36... -> PK37..., same shape, same length, wrong check digits.
        $response = $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => 'PK37SCBL0000001123456702',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('bank_account_number');
        $this->assertStringContainsString('check-digit', $response->json('message'));
    }

    public function test_a_correct_iban_is_accepted(): void
    {
        $profile = $this->seller('PK');

        $this->putJson(self::BASE, $this->bank())->assertOk();

        $this->assertSame('PK36SCBL0000001123456702', $profile->fresh()->bank_account_number);
    }

    /** P11-2 — an IBAN is welcome from anyone, whatever their country. */
    public function test_a_german_iban_is_accepted_from_a_seller_anywhere(): void
    {
        $this->seller('JP');

        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => 'DE89370400440532013000',
        ]))->assertOk();
    }

    public function test_an_iban_of_the_wrong_length_for_its_country_is_refused(): void
    {
        $this->seller('DE');

        // A German IBAN is 22 characters; this one is short.
        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => 'DE8937040044053201',
        ]))->assertStatus(422)->assertJsonValidationErrors('bank_account_number');
    }

    // ==================================================================
    // P11-1 — countries that need two fields
    // ==================================================================

    public function test_a_uk_seller_must_give_a_sort_code(): void
    {
        $this->seller('GB');

        $response = $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12345678',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('bank_branch_code');
        $this->assertStringContainsString('sort code', $response->json('message'));
    }

    public function test_a_uk_seller_with_a_sort_code_and_account_number_is_accepted(): void
    {
        $profile = $this->seller('GB');

        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12345678',
            'bank_branch_code' => '20-00-00',
        ]))->assertOk();

        $fresh = $profile->fresh();
        // Separators stripped, same as the account number.
        $this->assertSame('200000', $fresh->bank_branch_code);
        $this->assertSame('5678', $fresh->bank_account_last4);
    }

    public function test_a_sort_code_of_the_wrong_length_is_refused(): void
    {
        $this->seller('GB');

        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12345678',
            'bank_branch_code' => '2000',
        ]))->assertStatus(422)->assertJsonValidationErrors('bank_branch_code');
    }

    /** A UK seller who knows their IBAN is not forced to find a sort code. */
    public function test_a_uk_seller_may_use_an_iban_instead(): void
    {
        $this->seller('GB');

        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => 'GB29NWBK60161331926819',
        ]))->assertOk();
    }

    // ==================================================================
    // Storage and display
    // ==================================================================

    public function test_the_sort_code_is_encrypted_and_never_shown_in_full(): void
    {
        $profile = $this->seller('GB');

        $response = $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12345678',
            'bank_branch_code' => '200000',
        ]));

        $response->assertOk();
        // C57 — stored the same way as the number it belongs with.
        $raw = (string) DB::table('seller_profiles')->where('id', $profile->id)->value('bank_branch_code');
        $this->assertStringNotContainsString('200000', $raw);

        // Masked on the way out, like the account number.
        $response->assertJsonPath('data.bank.branch_code', '****00');
        $this->assertStringNotContainsString('200000', $response->getContent());
    }

    // ==================================================================
    // C58 — the sort code is part of the account's identity
    // ==================================================================

    public function test_changing_only_the_sort_code_counts_as_a_bank_change(): void
    {
        $profile = $this->seller('GB');

        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12345678',
            'bank_branch_code' => '200000',
        ]))->assertOk();

        // Pretend an admin had verified that account.
        $profile->forceFill([
            'bank_verification_status' => BankVerificationStatus::ADMIN_VERIFIED->value,
            'bank_verified_at' => now(),
        ])->save();

        // Same number, different branch — a DIFFERENT account.
        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12345678',
            'bank_branch_code' => '309876',
        ]))->assertOk();

        $fresh = $profile->fresh();
        $this->assertSame('309876', $fresh->bank_branch_code);
        // The old verification belonged to the old account.
        $this->assertNotSame(BankVerificationStatus::ADMIN_VERIFIED, $fresh->bank_verification_status);
        $this->assertNull($fresh->bank_verified_at);
    }

    // ==================================================================
    // P11-4 — a country we have no rules for
    // ==================================================================

    public function test_an_unlisted_country_accepts_a_plain_account_number(): void
    {
        $this->seller('JP');

        $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '01234567891234',
        ]))->assertOk();
    }

    public function test_letters_in_a_plain_account_number_are_refused(): void
    {
        $this->seller('PK');

        $response = $this->putJson(self::BASE, $this->bank([
            'bank_account_number' => '12AB34CD',
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('bank_account_number');
    }
}
