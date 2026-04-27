<?php

declare(strict_types=1);

namespace App\Domain\Email\Enums;

enum EmailStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case UNREAD = 'unread';
    case TRASHED = 'trashed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isSent(): bool
    {
        return $this === self::SENT;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isRead(): bool
    {
        return $this === self::READ;
    }

    public function isUnread(): bool
    {
        return $this === self::UNREAD;
    }

    public function isTrashed(): bool
    {
        return $this === self::TRASHED;
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::DELIVERED => 'Delivered',
            self::READ => 'Read',
            self::UNREAD => 'Unread',
            self::TRASHED => 'Trashed',
        };
    }

    public function isOpenState(): bool
    {
        return in_array($this, [self::UNREAD, self::READ], true);
    }

    public function isClosedState(): bool
    {
        return in_array($this, [self::TRASHED], true);
    }
}