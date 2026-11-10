<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * Where a variant's stock physically sits, and therefore which of the two
 * stock tables that variant is allowed to have a row in.
 *
 * This lives on the VARIANT, not on the product. A seller may hold the black
 * shirt in their own room in Lahore and dropship the white one from a supplier
 * in Guangzhou. On the product there would be no way to say that.
 *
 * Section 4 of BLUEPRINT puts variant_stock and dropship_sources in the
 * seller-owned group. This enum is the only thing that decides which of the
 * two a given variant must have.
 */
enum FulfilmentType: string
{
    case OWN_STOCK = 'own_stock';
    case DROPSHIP = 'dropship';

    /**
     * @return array<int, string>
     *
     * Same reason as ProductStatus::values() — the migration's column and
     * every validation rule read this, so a new case can never leave a
     * hand-typed list behind.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * True when variant_stock MUST carry a row for this variant, and when a
     * paid order decrements that row.
     */
    public function requiresLocalStock(): bool
    {
        return $this === self::OWN_STOCK;
    }

    /**
     * True when dropship_sources MUST carry a row for this variant (C14).
     *
     * WHY THIS IS NOT WRITTEN AS `! requiresLocalStock()`:
     * today the two are exact opposites, so one method and a `!` would give
     * the same two answers. They are two SEPARATE QUESTIONS though. The day a
     * third case arrives — a hybrid that holds a few locally and backfills
     * the rest from a supplier — it answers TRUE to both, and with one negated
     * method that case cannot be expressed at all. The bug would then show up
     * as a missing dropship_sources row in production instead of as a failing
     * test here.
     */
    public function requiresDropshipSource(): bool
    {
        return $this === self::DROPSHIP;
    }

    /**
     * Whether the number in our database is the truth, or a cached guess.
     *
     * For own stock it is the truth: we counted it, we hold it, and an order
     * decrements it inside the same transaction.
     *
     * For dropship it is whatever the supplier's feed last told us, and that
     * feed can be hours old. So a dropship variant must never be sold against
     * a hard count the shop treats as certain — the shopper screen shows a
     * dispatch window, and an order that the supplier cannot fill is a refund,
     * not a stock error. Any service that writes stock has to ask this before
     * trusting what it reads.
     */
    public function stockCountIsAuthoritative(): bool
    {
        return $this === self::OWN_STOCK;
    }

    public function label(): string
    {
        return match ($this) {
            self::OWN_STOCK => 'My own stock',
            self::DROPSHIP => 'Supplier ships it',
        };
    }
}
