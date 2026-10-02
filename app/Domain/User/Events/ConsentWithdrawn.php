<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use App\Models\UserConsent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConsentWithdrawn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly UserConsent $consent,
    ) {}
}
