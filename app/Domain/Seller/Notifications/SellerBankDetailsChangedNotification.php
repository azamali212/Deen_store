<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * C7 / Q2 — the seller's early warning. If an attacker hijacks the account
 * and swaps the payout bank, the REAL owner gets this email right away.
 */
final class SellerBankDetailsChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly string $maskedAccount,
        public readonly ?string $bankName,
        public readonly bool $isFirstTime,
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
            ->subject($this->isFirstTime
                ? 'Payout bank account added: '.$this->storeName
                : 'Security alert: payout bank account changed for '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->isFirstTime
                ? 'A payout bank account was added to your store "'.$this->storeName.'".'
                : 'The payout bank account for your store "'.$this->storeName.'" was just changed.')
            ->line('Account: '.$this->maskedAccount.($this->bankName !== null ? ' ('.$this->bankName.')' : ''))
            ->line('If you did NOT make this change, change your password immediately and contact support — your payouts may be at risk.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Security Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_bank_details_changed',
            'title' => $this->isFirstTime ? 'Payout account added' : 'Payout account changed',
            'store_name' => $this->storeName,
            'account' => $this->maskedAccount,
            'bank_name' => $this->bankName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
