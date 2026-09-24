<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerDocumentRenewalSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $renewalId,
        public readonly string $storeName,
        public readonly string $documentLabel,
        public readonly bool $payoutsFrozen,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Document renewal to review: '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('"'.$this->storeName.'" has uploaded a replacement '.$this->documentLabel.'.')
            ->line('Our automated check has already passed it. Please confirm it is genuine.');

        if ($this->payoutsFrozen) {
            $mail->line('This store\'s payouts are currently on hold because its documents expired, so this review is time-sensitive.');
        }

        return $mail->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_document_renewal_submitted',
            'title' => 'Document renewal to review',
            'renewal_id' => $this->renewalId,
            'store_name' => $this->storeName,
            'document' => $this->documentLabel,
            'payouts_frozen' => $this->payoutsFrozen,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
