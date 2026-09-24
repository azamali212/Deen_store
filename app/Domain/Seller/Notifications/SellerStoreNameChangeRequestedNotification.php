<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreNameChangeRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $sellerProfileId,
        public readonly string $currentName,
        public readonly string $requestedName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Store name change to review: '.$this->currentName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('"'.$this->currentName.'" has asked to be renamed to "'.$this->requestedName.'".')
            ->line('The name on their verified documents does not change — only the storefront name customers see.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_store_name_change_requested',
            'title' => 'Store name change to review',
            'seller_profile_id' => $this->sellerProfileId,
            'current_name' => $this->currentName,
            'requested_name' => $this->requestedName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
