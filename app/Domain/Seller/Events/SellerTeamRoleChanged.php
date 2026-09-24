<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Domain\Seller\Enums\SellerTeamRole;
use App\Models\SellerTeamMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SellerTeamRoleChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SellerTeamMember $member,
        public readonly SellerTeamRole $previousRole,
    ) {}
}
