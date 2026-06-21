<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\Events\Data\OtpEventData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OtpVerified
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OtpEventData $data,
    ) {}
}