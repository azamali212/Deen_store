<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Models\SellerProfile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Phase 8a — the ONE place that turns "these are the expiry dates" into
 * "this is the seller's KYC status". The daily command and the admin
 * review both go through here, so they can never disagree.
 */
final readonly class SellerKycService
{
    /** How long before expiry the seller is warned. */
    public const WARN_DAYS = 30;

    public function __construct(
        private SellerProfileRepositoryInterface $profiles,
    ) {}

    /**
     * C27 — NULL is not an expiry. AI verification is off by default, so
     * most stores have no date at all; reading NULL as "expired" would
     * freeze every existing seller the day AI is switched on.
     */
    public function statusFor(?CarbonInterface $cnic, ?CarbonInterface $licence, ?CarbonInterface $today = null): SellerKycStatus
    {
        $dates = array_filter([$cnic, $licence]);

        if ($dates === []) {
            return SellerKycStatus::VALID;
        }

        // CarbonImmutable::instance() both normalises the type and makes
        // addDays() below safe: handed a mutable Carbon, addDays() would
        // otherwise move the caller's own object.
        $today = CarbonImmutable::instance($today ?? CarbonImmutable::now())->startOfDay();
        $earliest = min($dates);

        return match (true) {
            $earliest->lessThan($today) => SellerKycStatus::EXPIRED,
            $earliest->lessThanOrEqualTo($today->addDays(self::WARN_DAYS)) => SellerKycStatus::EXPIRING_SOON,
            default => SellerKycStatus::VALID,
        };
    }

    public function statusForProfile(SellerProfile $profile, ?CarbonInterface $today = null): SellerKycStatus
    {
        return $this->statusFor($profile->cnic_expires_at, $profile->licence_expires_at, $today);
    }

    /**
     * Recompute and save. Returns the profile plus whether the status
     * actually MOVED — C29: the daily job emails on a state change, never
     * on a condition, or it would email the same seller every morning.
     *
     * @return array{profile: SellerProfile, previous: SellerKycStatus, changed: bool}
     */
    public function refresh(SellerProfile $profile, ?CarbonInterface $today = null): array
    {
        $previous = $profile->kycStatus();
        $next = $this->statusForProfile($profile, $today);

        if ($next === $previous) {
            return ['profile' => $profile, 'previous' => $previous, 'changed' => false];
        }

        $profile = $this->profiles->update($profile, [
            'kyc_status' => $next->value,
            'kyc_notified_at' => now(),
        ]);

        return ['profile' => $profile, 'previous' => $previous, 'changed' => true];
    }
}
