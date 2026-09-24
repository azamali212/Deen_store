<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerStoreReopenRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $sellerProfileId,
        public readonly string $storeName,
        public readonly ?string $closureReason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Reopen request: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The owner of "'.$this->storeName.'" has asked for their closed store to be reopened.');

        if ($this->closureReason !== null) {
            $mail->line('They gave this reason when they closed it: '.$this->closureReason);
        }

        return $mail
            ->line('Check that their bank details and identity documents are still valid before reopening.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_store_reopen_requested',
            'title' => 'Reopen request',
            'seller_profile_id' => $this->sellerProfileId,
            'store_name' => $this->storeName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
