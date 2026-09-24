<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreNameChangeReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $previousName,
        public readonly string $currentName,
        public readonly bool $approved,
        public readonly ?string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->name.',');

        if ($this->approved) {
            return $mail
                ->subject('Your store is now called '.$this->currentName)
                ->line('Your store has been renamed from "'.$this->previousName.'" to "'.$this->currentName.'".')
                ->salutation('Regards,'.PHP_EOL.config('app.name'));
        }

        return $mail
            ->subject('Name change not approved: '.$this->currentName)
            ->line('Your request to rename "'.$this->previousName.'" was not approved.')
            ->line($this->reason !== null ? 'Reason: '.$this->reason : 'Your store name is unchanged.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'seller_store_renamed' : 'seller_store_name_change_rejected',
            'title' => $this->approved ? 'Store renamed' : 'Name change not approved',
            'previous_name' => $this->previousName,
            'current_name' => $this->currentName,
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
