<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerDocumentRenewalReviewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly string $documentLabel,
        public readonly bool $approved,
        public readonly ?string $reason,
        public readonly ?string $validUntil,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->name.',');

        if ($this->approved) {
            $mail->subject('Document accepted: '.$this->storeName)
                ->line('Your replacement '.$this->documentLabel.' has been accepted.');

            if ($this->validUntil !== null) {
                $mail->line('Your records are now valid until '.$this->validUntil.'.');
            }

            return $mail
                ->line('Payouts for "'.$this->storeName.'" are no longer on hold for expired documents.')
                ->salutation('Regards,'.PHP_EOL.config('app.name'));
        }

        return $mail
            ->subject('Document not accepted: '.$this->storeName)
            ->line('The replacement '.$this->documentLabel.' you uploaded could not be accepted.')
            ->line('Reason: '.$this->reason)
            ->line('You can upload another copy of the same document and we will review it again.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->approved ? 'seller_document_renewal_approved' : 'seller_document_renewal_rejected',
            'title' => $this->approved ? 'Document accepted' : 'Document not accepted',
            'store_name' => $this->storeName,
            'document' => $this->documentLabel,
            'reason' => $this->reason,
            'valid_until' => $this->validUntil,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
