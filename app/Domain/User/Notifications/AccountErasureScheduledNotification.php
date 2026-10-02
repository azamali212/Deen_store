<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * C65 — this is the last email this account will ever receive, and it is
 * sent at REQUEST time, not at erasure time. By the time the sweep runs
 * there is no address left to send to.
 *
 * It is also the security control that makes the grace period worth having:
 * if someone else asked for this deletion, the real owner finds out now,
 * while there are still days left to stop it.
 */
final class AccountErasureScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly CarbonInterface $scheduledFor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account is scheduled for deletion')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We have received a request to delete your account.')
            ->line('It will be deleted on '.$this->scheduledFor->toFormattedDateString().'.')
            ->line('Until then you can cancel by signing in and cancelling the request from your privacy settings.')
            ->line('If you did not ask for this, sign in now, cancel the request and change your password.')
            ->line('Once the deletion runs it cannot be undone, and this is the last email we will be able to send you.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_erasure_scheduled',
            'title' => 'Account scheduled for deletion',
            'message' => 'Your account will be deleted on '.$this->scheduledFor->toFormattedDateString().'.',
            'scheduled_for' => $this->scheduledFor->toIso8601String(),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
