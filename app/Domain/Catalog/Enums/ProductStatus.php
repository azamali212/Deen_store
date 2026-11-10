<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * A product's lifecycle, and the ONLY place the allowed moves between those
 * states are written down.
 *
 * The five states come straight out of the two gates in BLUEPRINT section 3:
 * a seller builds in DRAFT, and on submit the gate sends the product to LIVE
 * (all four checks passed), to PENDING (a human has to look) or to REJECTED
 * (the AI blocked it at the door).
 */
enum ProductStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case LIVE = 'live';
    case REJECTED = 'rejected';
    case ARCHIVED = 'archived';

    /**
     * @return array<int, string>
     *
     * Derived, never hand-written — the same lesson as
     * SellerProfileStatus::values(). A hand-typed `in:draft,pending,live`
     * validation rule goes stale the day a case is added, and nothing fails
     * to tell you about it. The migration and every validation rule read
     * this method instead.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** The only state in which a product appears in the shop at all. */
    public function isVisibleToShoppers(): bool
    {
        return $this === self::LIVE;
    }

    /**
     * The seller may change anything, with no re-check, in exactly these two
     * states — because nothing about the product is public yet, so there is
     * nothing a bad edit could do to a shopper.
     *
     * Note LIVE is deliberately NOT in this list. A live product IS editable,
     * but only through the edit gate (D-CAT-6), where some fields go live at
     * once and others become a pending change. That is a different question
     * from this one, and it is answered by PendingChangeField, not here.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    /**
     * Every move this state is allowed to make. Anything not listed here is
     * refused by canTransitionTo().
     *
     * @return array<int, self>
     *
     * WHY THERE IS NO live -> pending MOVE, which is the first thing most
     * people reach for:
     *
     * D-CAT-6's key rule is that while a pending change is being checked,
     * the OLD version STAYS LIVE. So a live product whose new images are
     * waiting for moderation is still LIVE — the waiting lives in the
     * product_pending_changes table, not in the product's status. If this
     * enum allowed live -> pending, the shopper's page would go dark every
     * time a seller fixed a typo, and the whole point of the pending table
     * would be lost.
     *
     * PENDING therefore only ever happens BEFORE a product's first
     * publication. That is not a limitation; it is the design.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            // Submit sends it to exactly one of these three, and the seller
            // can also abandon a draft (products are never deleted, so
            // "abandon" means archive).
            self::DRAFT => [self::PENDING, self::LIVE, self::REJECTED, self::ARCHIVED],

            // An admin approves or rejects. DRAFT is the seller withdrawing
            // their own submission before anyone looked at it.
            self::PENDING => [self::LIVE, self::REJECTED, self::DRAFT],

            // DRAFT is the category-change path (D-CAT-6): new category means
            // new attributes, so the variants have to be rebuilt.
            self::LIVE => [self::DRAFT, self::ARCHIVED],

            self::REJECTED => [self::DRAFT, self::ARCHIVED],

            // Bringing a product back puts it at the START of the gate again.
            // Its prices and stock are stale and its images were never
            // re-checked, so it does not go straight back to LIVE.
            self::ARCHIVED => [self::DRAFT],
        };
    }

    /**
     * The guard every status change in the Catalog must pass through.
     *
     * Note that a state is never allowed to move to itself: LIVE is not in
     * LIVE's own list. A "change" that changes nothing is almost always a
     * double-submitted request, and failing it loudly is how that gets
     * noticed instead of writing a second audit row for nothing.
     */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'In review',
            self::LIVE => 'Live',
            self::REJECTED => 'Rejected',
            self::ARCHIVED => 'Archived',
        };
    }
}
