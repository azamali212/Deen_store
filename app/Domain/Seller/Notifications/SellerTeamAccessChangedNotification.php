<?php

declare(strict_types=1);

namespace App\Domain\Seller\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// To the member: your role changed, or your access was removed.
final class SellerTeamAccessChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $storeName,
        public readonly bool $removed,
        public readonly ?string $newRoleLabel = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting('Hello '.$notifiable->name.',');

        if ($this->removed) {
            return $mail
                ->subject('Your access to '.$this->storeName.' was removed')
                ->line('Your access to the store "'.$this->storeName.'" has been removed by the store owner.')
                ->line('Your own customer account is not affected — you can keep shopping as usual.')
                ->salutation('Regards,'.PHP_EOL.config('app.name'));
        }

        return $mail
            ->subject('Your role in '.$this->storeName.' changed')
            ->line('Your role in the store "'.$this->storeName.'" is now '.$this->newRoleLabel.'.')
            ->salutation('Regards,'.PHP_EOL.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->removed ? 'seller_team_access_removed' : 'seller_team_role_changed',
            'title' => $this->removed ? 'Store access removed' : 'Store role changed',
            'store_name' => $this->storeName,
            'role' => $this->newRoleLabel,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
