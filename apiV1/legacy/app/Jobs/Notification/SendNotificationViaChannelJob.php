<?php

declare(strict_types=1);

namespace App\Jobs\Notification;

use App\Domain\Notifications\Channels\ChannelManager;
use App\Domain\Notifications\Enums\DeliveryStatus;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\Repositories\NotificationRepository;
use App\Domain\Notifications\Repositories\TemplateRepository;
use App\Domain\Notifications\Support\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SendNotificationViaChannelJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 120];

    public int $timeout = 60;

    public function __construct(
        public readonly int $notificationId,
        public readonly string $channel,
        public readonly string $locale = 'en',
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        NotificationRepository $notificationRepository,
        TemplateRepository $templateRepository,
        TemplateRenderer $templateRenderer,
        ChannelManager $channelManager,
    ): void {
        $notification = $notificationRepository->findById($this->notificationId);
        if ($notification === null) {
            return;
        }

        $channel = NotificationChannel::tryFrom($this->channel);
        if ($channel === null) {
            return;
        }

        $delivery = $notificationRepository->firstOrCreateDelivery($notification, $channel);
        $attempt = max(1, $this->attempts());
        $payload = is_array($notification->payload) ? $notification->payload : [];

        try {
            $notificationRepository->markDelivery($delivery, DeliveryStatus::SENDING, $attempt, $payload);

            $template = $templateRepository->resolve((string) $notification->type, $channel, $this->locale);
            if ($template !== null) {
                $payload['subject'] = $template->subject_template !== null
                    ? $templateRenderer->render((string) $template->subject_template, $payload)
                    : null;

                $payload['body'] = $templateRenderer->render((string) $template->body_template, $payload);
                $payload['template_id'] = $template->id;
                $payload['template_version'] = $template->version;
            }

            $status = $channelManager->driver($channel)->send($notification, $delivery, $payload);
            $notificationRepository->markDelivery($delivery, $status, $attempt, $payload);
        } catch (Throwable $exception) {
            $notificationRepository->markDelivery(
                $delivery,
                DeliveryStatus::FAILED,
                $attempt,
                $payload,
                $exception->getMessage(),
            );

            $fresh = $notification->fresh('deliveries');
            $notificationRepository->refreshAggregateStatus($fresh ?? $notification);

            throw $exception;
        }

        $fresh = $notification->fresh('deliveries');
        $notificationRepository->refreshAggregateStatus($fresh ?? $notification);
    }
}