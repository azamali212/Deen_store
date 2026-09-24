<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerProfileStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';

    /**
     * P8-5 — a THIRD state, never folded into SUSPENDED. Suspended means
     * the platform stopped them; closed means they walked away. Storing
     * both as one value would make the audit trail lie about which
     * happened, and those two facts are not legally interchangeable.
     */
    case CLOSED = 'closed';

    /**
     * @return array<int, string>
     *
     * Derived, never hand-written. The admin filter had `in:active,
     * suspended` typed out; adding CLOSED silently left it unfilterable.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** The only state in which a store may be changed at all (C33). */
    public function isOpen(): bool
    {
        return $this === self::ACTIVE;
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::CLOSED => 'Closed',
        };
    }
}
