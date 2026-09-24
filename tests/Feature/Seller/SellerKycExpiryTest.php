<?php

declare(strict_types=1);

namespace Tests\Feature\Seller;

use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Notifications\SellerKycStatusNotification;
use App\Models\SellerProfile;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 8a — the nightly expiry sweep (BLUEPRINT section 13a).
 */
final class SellerKycExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Notification::fake();
    }

    private function store(array $overrides = []): SellerProfile
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $owner->assignRole('seller');

        return SellerProfile::factory()->create(['user_id' => $owner->id] + $overrides);
    }

    private function runSweep(): void
    {
        $this->artisan('seller:check-document-expiry')->assertSuccessful();
    }

    /**
     * C27 — the trap that would have frozen every existing seller the day
     * AI verification was switched on.
     */
    public function test_a_store_with_no_recorded_dates_is_never_touched(): void
    {
        $store = $this->store(['cnic_expires_at' => null, 'licence_expires_at' => null]);

        $this->runSweep();

        $store->refresh();
        $this->assertSame(SellerKycStatus::VALID, $store->kycStatus());
        $this->assertNull($store->kyc_notified_at);
        Notification::assertNothingSent();
    }

    public function test_a_date_far_in_the_future_is_left_alone(): void
    {
        $store = $this->store(['cnic_expires_at' => now()->addYears(3)->toDateString()]);

        $this->runSweep();

        $this->assertSame(SellerKycStatus::VALID, $store->refresh()->kycStatus());
        Notification::assertNothingSent();
    }

    public function test_a_document_expiring_within_thirty_days_warns_the_seller_once(): void
    {
        $store = $this->store(['cnic_expires_at' => now()->addDays(10)->toDateString()]);

        $this->runSweep();

        $store->refresh();
        $this->assertSame(SellerKycStatus::EXPIRING_SOON, $store->kycStatus());
        Notification::assertSentTo($store->user, SellerKycStatusNotification::class);
    }

    /** C29 — the whole reason kyc_notified_at exists. */
    public function test_running_again_the_next_day_does_not_email_the_same_seller_twice(): void
    {
        $store = $this->store(['cnic_expires_at' => now()->addDays(10)->toDateString()]);

        $this->runSweep();
        Notification::assertSentToTimes($store->user, SellerKycStatusNotification::class, 1);

        // Nothing changed, so nothing should be sent.
        $this->runSweep();
        $this->runSweep();

        Notification::assertSentToTimes($store->user, SellerKycStatusNotification::class, 1);
    }

    /** P8-2 — the store stays OPEN; only the money stops. */
    public function test_an_expired_document_stops_payouts_but_leaves_the_store_open(): void
    {
        $store = $this->store([
            'cnic_expires_at' => now()->subDay()->toDateString(),
            'bank_account_last4' => '1234',
            'bank_verification_status' => BankVerificationStatus::ADMIN_VERIFIED->value,
        ]);

        // Before the sweep the bank alone was enough.
        $this->assertTrue($store->isPayoutReady());

        $this->runSweep();

        $store->refresh();
        $this->assertSame(SellerKycStatus::EXPIRED, $store->kycStatus());

        // The store is NOT suspended — products keep selling.
        $this->assertFalse($store->isSuspended());

        // But the money is held, and the bank status is untouched (P8-3).
        $this->assertFalse($store->isPayoutReady());
        $this->assertTrue($store->isPayoutBankVerified());
        $this->assertSame(BankVerificationStatus::ADMIN_VERIFIED, $store->bank_verification_status);

        Notification::assertSentTo($store->user, SellerKycStatusNotification::class);
    }

    public function test_the_soonest_of_the_two_dates_decides(): void
    {
        $store = $this->store([
            'cnic_expires_at' => now()->addYears(4)->toDateString(),
            'licence_expires_at' => now()->subDays(2)->toDateString(),
        ]);

        $this->runSweep();

        $this->assertSame(SellerKycStatus::EXPIRED, $store->refresh()->kycStatus());
    }

    public function test_a_store_moves_from_warned_to_expired_and_is_told_again(): void
    {
        $store = $this->store(['cnic_expires_at' => now()->addDays(3)->toDateString()]);

        $this->runSweep();
        $this->assertSame(SellerKycStatus::EXPIRING_SOON, $store->refresh()->kycStatus());

        // Four days later the same document has expired — a real state
        // change, so the seller hears about it again.
        $this->travel(4)->days();
        $this->runSweep();

        $this->assertSame(SellerKycStatus::EXPIRED, $store->refresh()->kycStatus());
        Notification::assertSentToTimes($store->user, SellerKycStatusNotification::class, 2);
    }
}
