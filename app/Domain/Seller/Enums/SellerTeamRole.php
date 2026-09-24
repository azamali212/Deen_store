<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

use App\Domain\Permissions\Enums\SystemRole;

/**
 * A person's role INSIDE one store. Maps 1:1 onto the Spatie roles that
 * already exist in RolePermissionMap — this enum is the single place that
 * says who may do what within a store.
 */
enum SellerTeamRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case STAFF = 'staff';

    public function systemRole(): SystemRole
    {
        return match ($this) {
            self::OWNER => SystemRole::SELLER,
            self::MANAGER => SystemRole::SELLER_MANAGER,
            self::STAFF => SystemRole::SELLER_STAFF,
        };
    }

    /**
     * Roles an owner may hand out. OWNER is missing on purpose: a store
     * has exactly one owner, created at approval.
     *
     * @return array<int, string>
     */
    public static function assignableValues(): array
    {
        return [self::MANAGER->value, self::STAFF->value];
    }

    // P7-3 — building the team is an ownership power.
    public function canManageTeam(): bool
    {
        return $this === self::OWNER;
    }

    // Payout details follow payments.payouts, which only the owner has.
    /**
     * Phase 8a — a renewal replaces the OWNER's own identity document.
     * A manager running the shop has no business uploading someone else's
     * CNIC, so this stays with the owner alone.
     */
    /** P9-5 — a manager runs the shop; its NAME is the owner's identity. */
    public function canRenameStore(): bool
    {
        return $this === self::OWNER;
    }

    /** P8-5 — dissolving the business is nobody else's decision. */
    public function canCloseStore(): bool
    {
        return $this === self::OWNER;
    }

    public function canManageKycDocuments(): bool
    {
        return $this === self::OWNER;
    }

    public function canManageBankDetails(): bool
    {
        return $this === self::OWNER;
    }

    // Storefront content: owner and manager. Staff is read-only here.
    public function canEditStoreProfile(): bool
    {
        return $this === self::OWNER || $this === self::MANAGER;
    }

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::MANAGER => 'Manager',
            self::STAFF => 'Staff',
        };
    }
}
