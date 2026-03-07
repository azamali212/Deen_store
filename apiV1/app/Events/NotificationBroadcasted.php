<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class NotificationBroadcasted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly int $notificationId,
        public readonly int $userId,
        public readonly string $type,
        public readonly string $channel,
        public readonly array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel(sprintf('users.%d', $this->userId))];
    }

    public function broadcastAs(): string
    {
        // Match your config if you want
        return config('notification.channels.broadcast.event_name', 'notification.broadcasted');
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'type' => $this->type,
            'channel' => $this->channel,
            'payload' => $this->payload,
        ];
    }
}