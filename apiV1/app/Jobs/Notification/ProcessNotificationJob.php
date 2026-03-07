<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Domain\Notifications\Repositories\NotificationRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * @param array<int, string> $channels
     */
    public function __construct(
        public readonly int $notificationId,
        public readonly array $channels,
        public readonly string $locale = 'en',
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationRepository $notificationRepository): void
    {
        $notification = $notificationRepository->findById($this->notificationId);
        if ($notification === null) {
            return;
        }

        $channels = $this->channels !== []
            ? $this->channels
            : (array) ($notification->channels ?? []);

        foreach (array_values(array_unique($channels)) as $channel) {
            SendNotificationViaChannelJob::dispatch(
                notificationId: (int) $notification->id,
                channel: (string) $channel,
                locale: $this->locale,
            )->onQueue('notifications');
        }
    }
}