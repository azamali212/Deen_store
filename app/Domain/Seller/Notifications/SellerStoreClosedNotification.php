<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreClosedNotification extends Notification implements ShouldQueue
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
            ->subject('Your store is closed: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('"'.$this->storeName.'" is now closed, as you asked.')
            ->line('Nothing has been deleted. Your records are kept, and your customer account works exactly as before — you can still shop with it.')
            ->line('If you change your mind, ask us to reopen the store from your seller dashboard and our team will review it.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_store_closed',
            'title' => 'Store closed',
            'store_name' => $this->storeName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
