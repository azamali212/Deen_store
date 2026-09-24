<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerTeamMemberRemoved;
use App\Domain\Seller\Services\SellerTeamService;
use App\Models\SellerTeamMember;

final readonly class DeclineTeamInvitationAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    public function execute(int $userId, int $memberId): SellerTeamMember
    {
        $member = $this->service->declineInvitation($userId, $memberId);

        event(new SellerTeamMemberRemoved($member, declinedBySelf: true));

        return $member->load('sellerProfile:id,store_name');
    }
}
