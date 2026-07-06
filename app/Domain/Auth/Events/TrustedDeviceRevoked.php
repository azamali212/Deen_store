<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Models\TrustedDevice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TrustedDeviceRevoked
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly TrustedDevice $device,
    ) {}
}