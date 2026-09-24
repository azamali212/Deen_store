<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerBankProofReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly string $maskedAccount,
        public readonly bool $verified,
        public readonly ?string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->greeting('Hello '.$notifiable->name.',');

        if ($this->verified) {
            return $mail
                ->subject('Payout account verified: '.$this->storeName)
                ->line('Your payout bank account '.$this->maskedAccount.' has been verified by our team.')
                ->line('Payouts for "'.$this->storeName.'" can now be processed to this account.')
                ->salutation('Regards,'.PHP_EOL.config('app.name'));
        }

        return $mail
            ->subject('Bank statement not accepted: '.$this->storeName)
            ->line('The bank statement you uploaded for your payout account '.$this->maskedAccount.' could not be accepted.')
            ->line('Reason: '.$this->reason)
            ->line('Please upload a clearer or more recent statement for the SAME account, and we will review it again.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->verified ? 'seller_bank_verified' : 'seller_bank_proof_rejected',
            'title' => $this->verified ? 'Payout account verified' : 'Bank statement not accepted',
            'store_name' => $this->storeName,
            'account' => $this->maskedAccount,
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
