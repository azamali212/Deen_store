<?php

declare(strict_types=1);

namespace App\Domain\Email\DTO;

use InvalidArgumentException;

final readonly class ComposeEmailDTO
{
    /**
     * @param array<int, string> $toUserIds
     * @param array<int, string> $ccUserIds
     * @param array<int, string> $bccUserIds
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $senderId,
        public ?int $threadId,
        public ?int $parentEmailId,
        public ?string $subject,
        public ?string $body,
        public array $toUserIds,
        public array $ccUserIds = [],
        public array $bccUserIds = [],
        public string $priority = 'normal',
        public string $type = 'internal',
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
            senderId: (string) ($data['sender_id'] ?? ''),
            threadId: isset($data['thread_id']) ? (int) $data['thread_id'] : null,
            parentEmailId: isset($data['parent_email_id']) ? (int) $data['parent_email_id'] : null,
            subject: isset($data['subject']) ? trim((string) $data['subject']) : null,
            body: isset($data['body']) ? trim((string) $data['body']) : null,
            toUserIds: self::normalizeUserIds($data['to_user_ids'] ?? []),
            ccUserIds: self::normalizeUserIds($data['cc_user_ids'] ?? []),
            bccUserIds: self::normalizeUserIds($data['bcc_user_ids'] ?? []),
            priority: (string) ($data['priority'] ?? 'normal'),
            type: (string) ($data['type'] ?? 'internal'),
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<int, string>
     */
    public function allRecipientIds(): array
    {
        return array_values(array_unique([
            ...$this->toUserIds,
            ...$this->ccUserIds,
            ...$this->bccUserIds,
        ]));
    }

    /**
     * @return bool
     */
    public function hasRecipients(): bool
    {
        return $this->allRecipientIds() !== [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sender_id' => $this->senderId,
            'thread_id' => $this->threadId,
            'parent_email_id' => $this->parentEmailId,
            'subject' => $this->subject,
            'body' => $this->body,
            'to_user_ids' => $this->toUserIds,
            'cc_user_ids' => $this->ccUserIds,
            'bcc_user_ids' => $this->bccUserIds,
            'priority' => $this->priority,
            'type' => $this->type,
            'metadata' => $this->metadata,
        ];
    }

    private function guard(): void
    {
        if ($this->senderId === '') {
            throw new InvalidArgumentException('Sender id is required.');
        }

        if (!$this->hasRecipients()) {
            throw new InvalidArgumentException('At least one recipient is required.');
        }

        if ($this->subject !== null && mb_strlen($this->subject) > 255) {
            throw new InvalidArgumentException('Subject may not be greater than 255 characters.');
        }

        if (!in_array($this->priority, ['low', 'normal', 'high'], true)) {
            throw new InvalidArgumentException('Invalid email priority.');
        }

        if (!in_array($this->type, ['internal', 'system', 'manual'], true)) {
            throw new InvalidArgumentException('Invalid email type.');
        }

        if (
            ($this->subject === null || trim($this->subject) === '') &&
            ($this->body === null || trim($this->body) === '')
        ) {
            throw new InvalidArgumentException('Either subject or body is required.');
        }

        if (in_array($this->senderId, $this->allRecipientIds(), true)) {
            throw new InvalidArgumentException('Sender cannot be included as a recipient.');
        }
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private static function normalizeUserIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = array_map(
            static fn (mixed $id): string => trim((string) $id),
            $value
        );

        $ids = array_filter(
            $ids,
            static fn (string $id): bool => $id !== ''
        );

        return array_values(array_unique($ids));
    }
}