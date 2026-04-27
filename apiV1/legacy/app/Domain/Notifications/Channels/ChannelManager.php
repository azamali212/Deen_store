<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Channels;

use App\Domain\Notifications\Enums\NotificationChannel;
use InvalidArgumentException;

final class ChannelManager
{
    /**
     * @param array<string, NotificationChannelInterface> $channels
     */
    public function __construct(private readonly array $channels) {}

    public function driver(NotificationChannel $channel): NotificationChannelInterface
    {
        if (!array_key_exists($channel->value, $this->channels)) {
            throw new InvalidArgumentException(sprintf('Notification channel [%s] is not configured.', $channel->value));
        }

        return $this->channels[$channel->value];
    }
}