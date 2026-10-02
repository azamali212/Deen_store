<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * C64 — this event carries IDs, never the values that were just erased.
 *
 * The listener behind it writes an audit row, and an audit row saying
 * "we deleted john@example.com" would put the email straight back into
 * permanent storage — undoing, in the audit table, the erasure we just
 * performed. So the event has no User model on it at all: serialising
 * the model would drag the (now anonymised) attributes along, and the
 * temptation to log them would follow.
 */
final class AccountErased
{
    use Dispatchable;

    public function __construct(
        public readonly int $userId,
        public readonly string $uuid,
    ) {}
}
