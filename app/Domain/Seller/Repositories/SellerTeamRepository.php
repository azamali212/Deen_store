<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories;

use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Repositories\Contracts\SellerTeamRepositoryInterface;
use App\Domain\Seller\Repositories\Queries\SellerTeamQuery;
use App\Models\SellerTeamMember;
use Illuminate\Support\Collection;

final readonly class SellerTeamRepository implements SellerTeamRepositoryInterface
{
    public function __construct(
        private SellerTeamQuery $team,
    ) {}

    public function activeForUser(int $userId): ?SellerTeamMember
    {
        return $this->team->activeForUser($userId)->with('sellerProfile')->first();
    }

    public function liveForUser(int $userId): ?SellerTeamMember
    {
        return $this->team->liveForUser($userId)->first();
    }

    public function pendingInvitationsForUser(int $userId): Collection
    {
        return $this->team->pendingInvitationsForUser($userId)->get();
    }

    public function listForStore(int $sellerProfileId): Collection
    {
        return $this->team->forStore($sellerProfileId)->get();
    }

    public function activeForStore(int $sellerProfileId): Collection
    {
        return $this->team->activeForStore($sellerProfileId)->get();
    }

    public function findForStore(int $memberId, int $sellerProfileId): ?SellerTeamMember
    {
        return $this->team->byIdForStore($memberId, $sellerProfileId)->first();
    }

    public function findInvitationForUser(int $memberId, int $userId): ?SellerTeamMember
    {
        return $this->team->forUser($userId)
            ->whereKey($memberId)
            ->with('sellerProfile')
            ->first();
    }

    public function invite(int $sellerProfileId, int $userId, string $role, int $invitedBy): SellerTeamMember
    {
        // updateOrCreate: someone who was removed earlier gets their old
        // row re-used, which is also what the UNIQUE index expects.
        return SellerTeamMember::query()->updateOrCreate(
            [
                'seller_profile_id' => $sellerProfileId,
                'user_id' => $userId,
            ],
            [
                'role' => $role,
                'status' => SellerTeamMemberStatus::INVITED->value,
                'invited_by' => $invitedBy,
                'invited_at' => now(),
                'accepted_at' => null,
                'revoked_at' => null,
            ],
        );
    }

    public function createOwner(int $sellerProfileId, int $userId): SellerTeamMember
    {
        return SellerTeamMember::query()->create([
            'seller_profile_id' => $sellerProfileId,
            'user_id' => $userId,
            'role' => SellerTeamRole::OWNER->value,
            'status' => SellerTeamMemberStatus::ACTIVE->value,
            'accepted_at' => now(),
        ]);
    }

    public function update(SellerTeamMember $member, array $attributes): SellerTeamMember
    {
        $member->update($attributes);

        return $member->refresh();
    }
}
