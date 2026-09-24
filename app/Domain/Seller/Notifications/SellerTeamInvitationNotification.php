<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SellerTeamInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $memberId,
        public readonly string $storeName,
        public readonly string $roleLabel,
        public readonly string $invitedByName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to join '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->invitedByName.' has invited you to join the store "'.$this->storeName.'" as '.$this->roleLabel.'.')
            ->line('Sign in with this same account and accept the invitation to get access.')
            ->line('Your own customer account is not affected — you can keep shopping as usual.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_team_invitation',
            'title' => 'Store invitation',
            'member_id' => $this->memberId,
            'store_name' => $this->storeName,
            'role' => $this->roleLabel,
            'invited_by' => $this->invitedByName,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
