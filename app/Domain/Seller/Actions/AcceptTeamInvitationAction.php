<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerTeamMemberJoined;
use App\Domain\Seller\Services\SellerTeamService;
use App\Models\SellerTeamMember;

final readonly class AcceptTeamInvitationAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    public function execute(int $userId, int $memberId): SellerTeamMember
    {
        $member = $this->service->acceptInvitation($userId, $memberId);

        event(new SellerTeamMemberJoined($member));

        return $member->load(['user:id,name,email', 'sellerProfile:id,store_name']);
    }
}
