<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerTeamMemberRemoved;
use App\Domain\Seller\Services\SellerTeamService;
use App\Models\SellerTeamMember;

final readonly class RemoveTeamMemberAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    public function execute(int $ownerUserId, int $memberId): SellerTeamMember
    {
        $member = $this->service->remove($ownerUserId, $memberId);

        event(new SellerTeamMemberRemoved($member));

        return $member->load('user:id,name,email');
    }
}
