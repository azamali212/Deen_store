<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Contracts;

use App\Models\SellerTeamMember;
use Illuminate\Support\Collection;

interface SellerTeamRepositoryInterface
{
    public function activeForUser(int $userId): ?SellerTeamMember;

    /** Active OR invited — used to enforce "one person, one store" (P7-1). */
    public function liveForUser(int $userId): ?SellerTeamMember;

    public function pendingInvitationsForUser(int $userId): Collection;

    public function listForStore(int $sellerProfileId): Collection;

    public function findForStore(int $memberId, int $sellerProfileId): ?SellerTeamMember;

    public function findInvitationForUser(int $memberId, int $userId): ?SellerTeamMember;

    /** Re-inviting someone to the SAME store reuses their row. */
    public function invite(int $sellerProfileId, int $userId, string $role, int $invitedBy): SellerTeamMember;

    public function createOwner(int $sellerProfileId, int $userId): SellerTeamMember;

    public function update(SellerTeamMember $member, array $attributes): SellerTeamMember;
}
