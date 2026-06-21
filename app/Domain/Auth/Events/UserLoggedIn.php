<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use App\Domain\Auth\Events\Data\LoginEventData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserLoggedIn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LoginEventData $data,
    ) {}
}