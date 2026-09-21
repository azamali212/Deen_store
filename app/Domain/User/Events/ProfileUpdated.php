<?php

declare(strict_types=1);

namespace App\Domain\User\Events;

use App\Models\UserProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProfileUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly UserProfile $profile,
    ) {}
}
