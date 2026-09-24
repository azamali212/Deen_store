<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To every super_admin / platform_admin (Q3). Takes plain values instead of
 * the model so the queued job carries a small, stable payload.
 */
final class SellerApplicationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $applicationId,
        public readonly string $storeName,
        public readonly string $businessName,
        public readonly string $applicantEmail,
        public readonly bool $isResubmission,
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
        $subject = $this->isResubmission
            ? 'Seller application resubmitted: '.$this->storeName
            : 'New seller application: '.$this->storeName;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->isResubmission
                ? 'A previously rejected seller application has been corrected and resubmitted for review.'
                : 'A new seller application is waiting for review.')
            ->line('Application #'.$this->applicationId)
            ->line('Store name: '.$this->storeName)
            ->line('Business name: '.$this->businessName)
            ->line('Applicant: '.$this->applicantEmail)
            ->line('All 5 required documents have been uploaded.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->isResubmission
                ? 'seller_application_resubmitted'
                : 'seller_application_submitted',
            'title' => $this->isResubmission
                ? 'Seller application resubmitted'
                : 'New seller application',
            'application_id' => $this->applicationId,
            'store_name' => $this->storeName,
            'applicant_email' => $this->applicantEmail,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
