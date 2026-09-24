<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerApplicationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return [
            'mail',
            'database',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action needed on your seller application: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your seller application for "'.$this->storeName.'" could not be approved yet.')
            ->line('Reason: '.$this->reason)
            ->line('Please fix the points above (re-upload any document that was flagged) and submit your application again — you do not need to start over.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_application_rejected',
            'title' => 'Seller application needs changes',
            'message' => 'Your seller application was not approved yet.',
            'store_name' => $this->storeName,
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
