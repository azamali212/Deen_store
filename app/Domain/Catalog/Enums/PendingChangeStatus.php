<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Enums;

/**
 * The life of one row in product_pending_changes.
 *
 * Deliberately the same shape as ProductStatus — allowedTransitions() plus
 * canTransitionTo() — because it is the same kind of problem. Reading one
 * teaches the other, and a reviewer knows where to look for the rules.
 */
enum PendingChangeStatus: string
{
    case PENDING = 'pending';
    case APPLIED = 'applied';
    case REJECTED = 'rejected';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * True for the one state that is still waiting on a decision.
     *
     * This is the method that enforces "one open change per product at a
     * time": before accepting a new edit, PendingChangeService counts rows
     * whose status isOpen(). Allowing two would mean two different sets of
     * images waiting, and whichever was approved second would silently
     * overwrite the first with no record of the swap.
     */
    public function isOpen(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * @return array<int, self>
     *
     * NOTE THE TWO EMPTY ARRAYS, which ProductStatus never had: applied and
     * rejected are TERMINAL. A decided change is a historical fact, and a
     * fact does not change its mind. If a seller wants the rejected images
     * after all, that is a NEW row — so the record keeps both attempts and
     * both decisions, which is the whole point of an append-only trail.
     *
     * canTransitionTo() needs no special case for this: in_array() against an
     * empty array simply returns false, every time.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::APPLIED, self::REJECTED],
            self::APPLIED => [],
            self::REJECTED => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), strict: true);
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Waiting for review',
            self::APPLIED => 'Applied',
            self::REJECTED => 'Rejected',
        };
    }
}
