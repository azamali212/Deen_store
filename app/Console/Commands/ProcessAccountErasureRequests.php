<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\User\Services\AccountErasureService;
use Illuminate\Console\Command;

/**
 * P12-3 — the nightly erasure sweep.
 *
 * Runs at 04:00, after the KYC retention purge at 03:30, so a store's
 * documents have already lived out their own clock before the owner's
 * account is touched on the same night.
 */
final class ProcessAccountErasureRequests extends Command
{
    protected $signature = 'users:process-erasure-requests';

    protected $description = 'Erase accounts whose deletion grace period has run out.';

    public function handle(AccountErasureService $erasure): int
    {
        $result = $erasure->processDue();

        $this->info(sprintf(
            'Erasure sweep: %d erased, %d cancelled (blocked), %d failed.',
            $result['erased'],
            $result['cancelled'],
            $result['failed'],
        ));

        $this->line(sprintf(
            'Grace period in use: %d days.',
            (int) config('privacy.erasure_grace_days'),
        ));

        return self::SUCCESS;
    }
}
