<?php

declare(strict_types=1);

namespace App\Domain\Email\DTO;

use InvalidArgumentException;

final readonly class ChangeEmailStatusDTO
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $emailId,
        public string $userId,
        public string $action,
        public array $metadata = [],
    ) {
        $this->guard();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            emailId: (int) ($data['email_id'] ?? 0),
            userId: (string) ($data['user_id'] ?? ''),
            action: (string) ($data['action'] ?? ''),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function isReadAction(): bool
    {
        return $this->action === 'mark_read';
    }

    public function isUnreadAction(): bool
    {
        return $this->action === 'mark_unread';
    }

    public function isTrashAction(): bool
    {
        return $this->action === 'move_to_trash';
    }

    public function isRestoreAction(): bool
    {
        return $this->action === 'restore';
    }

    public function isStarAction(): bool
    {
        return $this->action === 'star';
    }

    public function isUnstarAction(): bool
    {
        return $this->action === 'unstar';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'email_id' => $this->emailId,
            'user_id' => $this->userId,
            'action' => $this->action,
            'metadata' => $this->metadata,
        ];
    }

    private function guard(): void
    {
        if ($this->emailId <= 0) {
            throw new InvalidArgumentException('Valid email id is required.');
        }

        if ($this->userId === '') {
            throw new InvalidArgumentException('User id is required.');
        }

        if (!in_array($this->action, [
            'mark_read',
            'mark_unread',
            'move_to_trash',
            'restore',
            'star',
            'unstar',
        ], true)) {
            throw new InvalidArgumentException('Invalid email status action.');
        }
    }
}