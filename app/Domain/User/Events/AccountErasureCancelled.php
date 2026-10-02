<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AccountErasureCancelled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * $byUser is false when the nightly sweep cancelled it — the account
     * picked up a blocker (an open store) during the grace period and the
     * request could no longer be honoured (C63).
     */
    public function __construct(
        public readonly User $user,
        public readonly bool $byUser = true,
        public readonly ?string $reason = null,
    ) {}
}
