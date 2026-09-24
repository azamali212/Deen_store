<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Events\SellerTeamMemberInvited;
use App\Domain\Seller\Services\SellerTeamService;
use App\Models\SellerTeamMember;

final readonly class InviteTeamMemberAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    public function execute(int $ownerUserId, string $email, SellerTeamRole $role): SellerTeamMember
    {
        $member = $this->service->invite($ownerUserId, $email, $role);

        event(new SellerTeamMemberInvited($member));

        return $member->load('user:id,name,email');
    }
}
