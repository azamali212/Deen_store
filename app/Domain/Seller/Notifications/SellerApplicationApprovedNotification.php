<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
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
            ->subject('Your business is approved: '.$this->storeName)
            ->greeting('Congratulations '.$notifiable->name.'!')
            ->line('Your seller application for "'.$this->storeName.'" has been approved.')
            ->line('You can now open the Seller Dashboard from your account (use "Switch to Seller Dashboard").')
            ->line('Next step: complete your business profile — logo, description, business address and payout bank details.')
            ->line('Your customer account stays exactly as it was, so you can keep shopping too.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_application_approved',
            'title' => 'Business approved',
            'message' => 'Your seller application for "'.$this->storeName.'" has been approved.',
            'store_name' => $this->storeName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
