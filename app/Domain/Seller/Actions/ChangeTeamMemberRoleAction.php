<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Events\SellerTeamRoleChanged;
use App\Domain\Seller\Services\SellerTeamService;
use App\Models\SellerTeamMember;

final readonly class ChangeTeamMemberRoleAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    public function execute(int $ownerUserId, int $memberId, SellerTeamRole $role): SellerTeamMember
    {
        $result = $this->service->changeRole($ownerUserId, $memberId, $role);

        // Fired after the transaction commits (C15).
        event(new SellerTeamRoleChanged($result['member'], $result['previous_role']));

        return $result['member']->load('user:id,name,email');
    }
}
