<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired every time a user downloads their own GDPR-style data export —
 * purely so it lands in the audit trail (compliance record of when/who
 * exported their data), not because anything else needs to react to it.
 */
final class UserDataExported
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}
