<?php

declare(strict_types=1);

namespace App\Domain\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountErasureCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly bool $byUser,
        private readonly ?string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your account will not be deleted')
            ->greeting('Hello '.$notifiable->name.',');

        if ($this->byUser) {
            return $mail
                ->line('You cancelled the request to delete your account. Nothing has been removed and your account is unchanged.')
                ->line('If it was not you who cancelled it, please change your password immediately.')
                ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
        }

        return $mail
            ->line('We could not go ahead with deleting your account, so the request has been cancelled and nothing was removed.')
            ->line('Reason: '.($this->reason ?? 'The account still has obligations attached to it.'))
            ->line('Once that is resolved you can ask for deletion again.')
            ->salutation('Regards,'.PHP_EOL.config('app.name').' Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'account_erasure_cancelled',
            'title' => 'Deletion request cancelled',
            'message' => $this->byUser
                ? 'You cancelled the request to delete your account.'
                : 'Your deletion request was cancelled because it could not be completed.',
            'cancelled_by' => $this->byUser ? 'user' : 'system',
            'reason' => $this->reason,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
