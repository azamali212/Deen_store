<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// To the OWNER — somebody now has access to their store.
final class SellerTeamMemberJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly string $memberName,
        public readonly string $memberEmail,
        public readonly string $roleLabel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New team member in '.$this->storeName)
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->memberName.' ('.$this->memberEmail.') accepted your invitation and joined "'.$this->storeName.'" as '.$this->roleLabel.'.')
            ->line('If this was not expected, remove them from your team settings right away.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'seller_team_member_joined',
            'title' => 'New team member',
            'store_name' => $this->storeName,
            'member_name' => $this->memberName,
            'member_email' => $this->memberEmail,
            'role' => $this->roleLabel,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
