<?php

declare(strict_types=1);

namespace App\Domain\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TrustedDeviceRevokedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $deviceName,
        private readonly string $browser,
        private readonly string $operatingSystem,
    ) {}

    public function via(
        object $notifiable,
    ): array {

        return [

            'mail',

            'database',

        ];
    }

    public function toMail(
        object $notifiable,
    ): MailMessage {

        return (new MailMessage)

            ->subject('Trusted Device Removed')

            ->greeting(
                'Hello '.$notifiable->name.','
            )
            ->line(
                'A trusted device has been removed from your account.'
            )
            ->line(
                'Device: '.$this->deviceName
            )
            ->line(
                'Browser: '.$this->browser
            )
            ->line(
                'Operating System: '.$this->operatingSystem
            )
            ->line(
                'If you removed this device, no further action is required.'
            )
            ->line(
                'If you did not perform this action, please change your password immediately.'
            )
            ->salutation(
                'Regards,'.PHP_EOL.config('app.name').' Team'
            );
    }

    public function toArray(
        object $notifiable,
    ): array {

        return [

            'type' => 'trusted_device_revoked',
            'title' => 'Trusted Device Removed',
            'message' => 'A trusted device has been removed from your account.',
            'device_name' => $this->deviceName,
            //'browser' => $this->browser,
            //'operating_system' => $this->operatingSystem,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}