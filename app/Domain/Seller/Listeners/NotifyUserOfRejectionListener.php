<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerApplicationRejected;
use App\Domain\Seller\Notifications\SellerApplicationRejectedNotification;

final class NotifyUserOfRejectionListener
{
    public function handle(SellerApplicationRejected $event): void
    {
        $event->application->loadMissing('user')->user->notify(
            new SellerApplicationRejectedNotification(
                storeName: $event->application->store_name,
                reason: (string) $event->application->rejection_reason,
            ),
        );
    }
}
