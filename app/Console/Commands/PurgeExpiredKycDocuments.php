<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Seller\Services\SellerKycRetentionService;
use Illuminate\Console\Command;

/**
 * Phase 9a — the nightly retention sweep.
 *
 * Deletes FILES, never rows (P9-2). Every row keeps saying a document
 * existed, who reviewed it and when, plus the date its file was purged.
 */
final class PurgeExpiredKycDocuments extends Command
{
    protected $signature = 'seller:purge-expired-kyc-documents';

    protected $description = 'Delete identity documents past their retention window, keeping the records that say they existed.';

    public function handle(SellerKycRetentionService $retention): int
    {
        $result = $retention->purge();

        $this->info(sprintf(
            'KYC retention sweep: %d superseded, %d from closed stores, %d failed.',
            $result['superseded'],
            $result['closed'],
            $result['failed'],
        ));

        $this->line(sprintf(
            'Windows in use: closed stores %d days, superseded %d days.',
            (int) config('seller.kyc_retention_days'),
            (int) config('seller.superseded_retention_days'),
        ));

        return self::SUCCESS;
    }
}
