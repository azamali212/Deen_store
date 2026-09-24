<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Events\SellerKycStatusChanged;
use App\Domain\Seller\Services\SellerKycService;
use App\Models\SellerProfile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 8a — the nightly sweep for expiring KYC documents.
 *
 * This command is the whole reason the expiry DATES live in their own
 * plain columns (P8-1): what the AI read is stored encrypted, and
 * ciphertext cannot be queried, so this scan would otherwise be impossible.
 */
final class CheckSellerDocumentExpiry extends Command
{
    protected $signature = 'seller:check-document-expiry';

    protected $description = 'Warn sellers whose identity documents expire soon, and hold payouts for those that already expired.';

    public function handle(SellerKycService $kyc): int
    {
        $warned = 0;
        $expired = 0;

        SellerProfile::query()
            // C27 — a store with NO recorded date is untouched. Most stores
            // have none (AI verification is off by default); scanning them
            // as "expired" would freeze every seller's payouts at once.
            ->where(fn (Builder $query): Builder => $query
                ->whereNotNull('cnic_expires_at')
                ->orWhereNotNull('licence_expires_at'))
            ->orderBy('id')
            ->chunkById(200, function ($profiles) use ($kyc, &$warned, &$expired): void {
                foreach ($profiles as $profile) {
                    $result = $kyc->refresh($profile);

                    // C29 — only a real state CHANGE is reported. Without
                    // this the same seller would be emailed every morning
                    // for thirty days.
                    if (! $result['changed']) {
                        continue;
                    }

                    $current = $result['profile']->kycStatus();

                    event(new SellerKycStatusChanged(
                        $result['profile'],
                        $result['previous'],
                        $current,
                    ));

                    match ($current) {
                        SellerKycStatus::EXPIRED => $expired++,
                        SellerKycStatus::EXPIRING_SOON => $warned++,
                        default => null,
                    };
                }
            });

        $this->info(sprintf('Document expiry check done: %d warned, %d expired.', $warned, $expired));

        return self::SUCCESS;
    }
}
