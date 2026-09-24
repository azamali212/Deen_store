<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// To every seller reviewer (super_admin + platform_admin).
final class SellerBankUnverifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $sellerProfileId,
        public readonly string $storeName,
        public readonly string $maskedAccount,
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
            ->subject('Payout bank needs verification: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The seller "'.$this->storeName.'" set a payout bank account ('.$this->maskedAccount.') that does NOT match the bank statement verified during onboarding.')
            ->line('The account was saved but marked "mismatch". Payouts to it should stay on hold until an admin confirms it belongs to the seller.')
            ->line('Seller profile #'.$this->sellerProfileId)
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Security Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_bank_unverified',
            'title' => 'Payout bank needs verification',
            'seller_profile_id' => $this->sellerProfileId,
            'store_name' => $this->storeName,
            'account' => $this->maskedAccount,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
