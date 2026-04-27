<?php

declare(strict_types=1);

namespace App\Domain\Notifications\DTO;

use App\Domain\Notifications\Enums\NotificationChannel;

final class SendNotificationDTO
{
    /**
     * @param array<int, NotificationChannel> $channels
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $type,
        public readonly int $recipientId,
        public readonly string $recipientType = 'user',
        public readonly array $payload = [],
        public readonly array $channels = [],
        public readonly string $locale = 'en',
        public readonly ?int $actorId = null,
        public readonly ?string $idempotencyKey = null,
    ) {}
}