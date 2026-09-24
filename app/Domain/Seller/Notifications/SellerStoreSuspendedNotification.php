<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your store has been suspended: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your store "'.$this->storeName.'" has been suspended by our team.')
            ->line('Reason: '.$this->reason)
            ->line('While it is suspended you can still sign in and view your store, but you cannot make changes to it.')
            ->line('Your customer account is not affected — you can keep shopping as usual.')
            ->line('If you believe this is a mistake, please reply to this email or contact support.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_store_suspended',
            'title' => 'Store suspended',
            'store_name' => $this->storeName,
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
