<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\Events\Data\LogoutEventData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserLoggedOut
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LogoutEventData $data,
    ) {}
}