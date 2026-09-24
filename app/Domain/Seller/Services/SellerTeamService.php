<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Enums\SellerTeamMemberStatus;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Exceptions\SellerProfileNotFoundException;
use App\Domain\Seller\Exceptions\SellerStoreClosedException;
use App\Domain\Seller\Exceptions\SellerStoreSuspendedException;
use App\Domain\Seller\Exceptions\SellerTeamMemberNotFoundException;
use App\Domain\Seller\Exceptions\SellerTeamPermissionException;
use App\Domain\Seller\Exceptions\TeamInvitationNotAllowedException;
use App\Domain\Seller\Exceptions\TeamMembershipConflictException;
use App\Domain\Seller\Repositories\Contracts\SellerTeamRepositoryInterface;
use App\Models\SellerTeamMember;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 7 — the ONE place that answers "which store is this user in, and
 * what may they do there" (BLUEPRINT section 12).
 *
 * Before this existed the answer came from seller_profiles.user_id, which
 * only ever knew about the owner.
 */
final readonly class SellerTeamService
{
    public function __construct(
        private SellerTeamRepositoryInterface $team,
    ) {}

    /**
     * The caller's active membership — every seller endpoint starts here.
     */
    public function membershipFor(int $userId): SellerTeamMember
    {
        return $this->team->activeForUser($userId)
            ?? throw SellerProfileNotFoundException::forUser($userId);
    }

    public function list(int $userId): Collection
    {
        return $this->team->listForStore($this->membershipFor($userId)->seller_profile_id);
    }

    public function pendingInvitations(int $userId): Collection
    {
        return $this->team->pendingInvitationsForUser($userId);
    }

    /**
     * P7-3 — only the owner. P7-2 — the email must already have an account.
     */
    public function invite(int $ownerUserId, string $email, SellerTeamRole $role): SellerTeamMember
    {
        $owner = $this->membershipFor($ownerUserId);
        $this->ensureStoreOpen($owner);
        $this->ensureCan($owner, fn (SellerTeamRole $r): bool => $r->canManageTeam(), 'manage the team');

        $invitee = User::query()->where('email', $email)->first()
            ?? throw TeamInvitationNotAllowedException::noAccountForEmail($email);

        if ((int) $invitee->id === $ownerUserId) {
            throw TeamInvitationNotAllowedException::cannotInviteYourself();
        }

        // P7-1 — one person, one store.
        $existing = $this->team->liveForUser((int) $invitee->id);

        if ($existing !== null) {
            throw (int) $existing->seller_profile_id === (int) $owner->seller_profile_id
                ? TeamMembershipConflictException::alreadyInThisStore($email)
                : TeamMembershipConflictException::alreadyInAStore($email);
        }

        return $this->team->invite(
            (int) $owner->seller_profile_id,
            (int) $invitee->id,
            $role->value,
            $ownerUserId,
        );
    }

    /**
     * @return array{member: SellerTeamMember, previous_role: SellerTeamRole}
     */
    public function changeRole(int $ownerUserId, int $memberId, SellerTeamRole $role): array
    {
        return DB::transaction(function () use ($ownerUserId, $memberId, $role): array {
            $owner = $this->membershipFor($ownerUserId);
            $this->ensureStoreOpen($owner);
            $this->ensureCan($owner, fn (SellerTeamRole $r): bool => $r->canManageTeam(), 'manage the team');

            $member = $this->findInStore($memberId, (int) $owner->seller_profile_id);

            // C23 — the store must keep exactly one owner.
            if ($member->isOwner()) {
                throw SellerTeamPermissionException::ownCannotBeChanged();
            }

            $previous = $member->role;

            $member = $this->team->update($member, ['role' => $role->value]);

            // An accepted member carries a Spatie role; swap it. An invited
            // one has none yet — they get the new role when they accept.
            if ($member->isActive()) {
                $this->swapSystemRole($member->user, $previous, $role);
            }

            return ['member' => $member, 'previous_role' => $previous];
        });
    }

    public function remove(int $ownerUserId, int $memberId): SellerTeamMember
    {
        return DB::transaction(function () use ($ownerUserId, $memberId): SellerTeamMember {
            $owner = $this->membershipFor($ownerUserId);
            $this->ensureStoreOpen($owner);
            $this->ensureCan($owner, fn (SellerTeamRole $r): bool => $r->canManageTeam(), 'manage the team');

            $member = $this->findInStore($memberId, (int) $owner->seller_profile_id);

            if ($member->isOwner()) {
                throw SellerTeamPermissionException::ownCannotBeChanged();
            }

            $wasActive = $member->isActive();

            // The row is kept, not deleted, so the audit trail still shows
            // who used to have access.
            $member = $this->team->update($member, [
                'status' => SellerTeamMemberStatus::REVOKED->value,
                'revoked_at' => now(),
            ]);

            if ($wasActive) {
                $member->user->removeRole($member->role->systemRole()->value);
            }

            return $member;
        });
    }

    /**
     * C21 — reached WITHOUT the seller role, because the invitee does not
     * have one yet. That is the whole point of this step.
     */
    public function acceptInvitation(int $userId, int $memberId): SellerTeamMember
    {
        return DB::transaction(function () use ($userId, $memberId): SellerTeamMember {
            $member = $this->findInvitation($userId, $memberId);

            // Nobody joins a store that is not open — suspended by us, or
            // closed by its own owner (C33).
            $this->ensureStoreOpen($member);

            $member = $this->team->update($member, [
                'status' => SellerTeamMemberStatus::ACTIVE->value,
                'accepted_at' => now(),
            ]);

            // assignRole ADDS — their customer role stays untouched (C4).
            $member->user->assignRole($member->role->systemRole()->value);

            return $member;
        });
    }

    public function declineInvitation(int $userId, int $memberId): SellerTeamMember
    {
        $member = $this->findInvitation($userId, $memberId);

        return $this->team->update($member, [
            'status' => SellerTeamMemberStatus::REVOKED->value,
            'revoked_at' => now(),
        ]);
    }

    /**
     * P8-5 — the store closed. Access goes, the TEAM ROWS STAY (C31), so
     * reopening restores exactly the team that was there instead of making
     * the owner rebuild it by hand.
     *
     * C39 — the OWNER keeps their seller role. If it came off too, they
     * could not reach any seller route, including the one that asks for
     * the store to be reopened — they would be locked out of their own
     * exit. This mirrors suspension (P6-1): the panel still opens, it just
     * refuses every write.
     */
    public function stripStoreRoles(int $sellerProfileId): void
    {
        foreach ($this->team->activeForStore($sellerProfileId) as $member) {
            if ($member->isOwner()) {
                continue;
            }

            $member->user?->removeRole($member->role->systemRole()->value);
        }
    }

    public function restoreStoreRoles(int $sellerProfileId): void
    {
        foreach ($this->team->activeForStore($sellerProfileId) as $member) {
            // assignRole is idempotent, so the owner (who kept theirs) is
            // simply re-confirmed rather than duplicated.
            $member->user?->assignRole($member->role->systemRole()->value);
        }
    }

    /**
     * Used by SellerProfileService before every write.
     *
     * @param  callable(SellerTeamRole): bool  $check
     */
    public function ensureCan(SellerTeamMember $member, callable $check, string $action): void
    {
        if (! $check($member->role)) {
            throw SellerTeamPermissionException::cannot($action);
        }
    }

    /**
     * P6/P7/P8 — a store that is not open is frozen for team changes:
     * nobody is hired, promoted or fired, and nobody joins. One helper for
     * every path, so a new non-open state cannot miss one (C33).
     */
    private function ensureStoreOpen(SellerTeamMember $member): void
    {
        $profile = $member->sellerProfile;

        if ($profile->isSuspended()) {
            throw SellerStoreSuspendedException::forProfile((int) $member->seller_profile_id);
        }

        if ($profile->isClosed()) {
            throw SellerStoreClosedException::forProfile((int) $member->seller_profile_id);
        }
    }

    private function findInStore(int $memberId, int $sellerProfileId): SellerTeamMember
    {
        return $this->team->findForStore($memberId, $sellerProfileId)
            ?? throw SellerTeamMemberNotFoundException::withId($memberId);
    }

    private function findInvitation(int $userId, int $memberId): SellerTeamMember
    {
        // Scoped by user id: you can only ever act on YOUR invitation.
        $member = $this->team->findInvitationForUser($memberId, $userId)
            ?? throw SellerTeamMemberNotFoundException::invitation($memberId);

        if ($member->status !== SellerTeamMemberStatus::INVITED) {
            throw TeamMembershipConflictException::invitationNotPending();
        }

        return $member;
    }

    private function swapSystemRole(User $user, SellerTeamRole $from, SellerTeamRole $to): void
    {
        $user->removeRole($from->systemRole()->value);
        $user->assignRole($to->systemRole()->value);
    }
}
