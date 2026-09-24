<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreReactivatedNotification extends Notification implements ShouldQueue
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
            ->subject('Your store is active again: '.$this->storeName)
            ->greeting('Good news '.$notifiable->name.'!')
            ->line('The suspension on your store "'.$this->storeName.'" has been lifted.')
            ->line('You can manage your store as normal again.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_store_reactivated',
            'title' => 'Store reactivated',
            'store_name' => $this->storeName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
