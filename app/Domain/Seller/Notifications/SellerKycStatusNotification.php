<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use App\Domain\Seller\Enums\SellerKycStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * P8-2 — the store stays OPEN either way. The warning is about payouts,
 * not about shutting a business down.
 */
final class SellerKycStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly SellerKycStatus $status,
        public readonly ?string $expiresOn,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->name.',');

        if ($this->status === SellerKycStatus::EXPIRED) {
            return $mail
                ->subject('Action needed: documents expired for '.$this->storeName)
                ->line('The identity documents on file for "'.$this->storeName.'" expired on '.$this->expiresOn.'.')
                ->line('Your store stays open and your customers can still order — but payouts are on hold until you upload a valid document.')
                ->line('Upload a replacement from your seller dashboard and our team will review it.')
                ->salutation('Regards,'.PHP_EOL.config('app.name'));
        }

        return $mail
            ->subject('Your documents expire soon: '.$this->storeName)
            ->line('The identity documents on file for "'.$this->storeName.'" expire on '.$this->expiresOn.'.')
            ->line('Please upload a replacement before then. If they expire, your store keeps selling but payouts will be held until a valid document is on file.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_kyc_'.$this->status->value,
            'title' => $this->status === SellerKycStatus::EXPIRED
                ? 'Documents expired — payouts on hold'
                : 'Documents expiring soon',
            'store_name' => $this->storeName,
            'expires_on' => $this->expiresOn,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
