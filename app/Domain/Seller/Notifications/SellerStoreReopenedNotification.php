<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreReopenedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your store is open again: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('"'.$this->storeName.'" has been reopened.')
            ->line('Your team has their access back, with the same roles they had before.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_store_reopened',
            'title' => 'Store reopened',
            'store_name' => $this->storeName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
