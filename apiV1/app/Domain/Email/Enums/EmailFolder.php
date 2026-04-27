<?php

declare(strict_types=1);

namespace App\Domain\Email\Enums;

enum EmailFolder: string
{
    case INBOX = 'inbox';
    case SENT = 'sent';
    case DRAFT = 'draft';
    case TRASH = 'trash';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isInbox(): bool
    {
        return $this === self::INBOX;
    }

    public function isSent(): bool
    {
        return $this === self::SENT;
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isTrash(): bool
    {
        return $this === self::TRASH;
    }

    public function label(): string
    {
        return match ($this) {
            self::INBOX => 'Inbox',
            self::SENT => 'Sent',
            self::DRAFT => 'Draft',
            self::TRASH => 'Trash',
        };
    }
}