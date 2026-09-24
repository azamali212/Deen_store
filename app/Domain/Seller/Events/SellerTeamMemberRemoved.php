<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Models\SellerTeamMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerTeamMemberRemoved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerTeamMember $member,
        // true = the invitee declined it themselves, false = the owner
        // removed them. Only the second one gets an email.
        public readonly bool $declinedBySelf = false,
    ) {}
}
