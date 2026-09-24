<?php

declare(strict_types=1);

namespace App\Domain\Seller\Repositories\Queries;

use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Models\SellerTeamMember;
use Illuminate\Database\Eloquent\Builder;

final class SellerTeamQuery
{
    public function forUser(int $userId): Builder
    {
        return SellerTeamMember::query()->where('user_id', $userId);
    }

    public function activeForUser(int $userId): Builder
    {
        return $this->forUser($userId)
            ->where('status', SellerTeamMemberStatus::ACTIVE->value);
    }

    // Anything that is not revoked blocks a new invite elsewhere (P7-1).
    public function liveForUser(int $userId): Builder
    {
        return $this->forUser($userId)
            ->whereIn('status', [
                SellerTeamMemberStatus::ACTIVE->value,
                SellerTeamMemberStatus::INVITED->value,
            ]);
    }

    public function pendingInvitationsForUser(int $userId): Builder
    {
        return $this->forUser($userId)
            ->where('status', SellerTeamMemberStatus::INVITED->value)
            ->with(['sellerProfile:id,store_name', 'invitedBy:id,name']);
    }

    public function forStore(int $sellerProfileId): Builder
    {
        return SellerTeamMember::query()
            ->where('seller_profile_id', $sellerProfileId)
            ->with('user:id,name,email')
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'manager' THEN 2 ELSE 3 END")
            ->orderBy('id');
    }

    public function byIdForStore(int $memberId, int $sellerProfileId): Builder
    {
        return SellerTeamMember::query()
            ->whereKey($memberId)
            ->where('seller_profile_id', $sellerProfileId);
    }
}
