<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Events;

use App\Models\ModerationFlag;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ProfileFlaggedForReview
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ModerationFlag $flag,
    ) {}
}
