<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// To the seller reviewers — a bank statement is waiting for review.
final class SellerBankProofPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $sellerProfileId,
        public readonly string $storeName,
        public readonly string $maskedAccount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bank statement to review: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The seller "'.$this->storeName.'" uploaded a bank statement for their payout account '.$this->maskedAccount.'.')
            ->line('Please open the statement and confirm it belongs to this seller before payouts are allowed.')
            ->line('Store #'.$this->sellerProfileId)
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_bank_proof_pending',
            'title' => 'Bank statement to review',
            'seller_profile_id' => $this->sellerProfileId,
            'store_name' => $this->storeName,
            'account' => $this->maskedAccount,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
