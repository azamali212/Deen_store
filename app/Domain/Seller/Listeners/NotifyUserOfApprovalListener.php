<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerApplicationApproved;
use App\Domain\Seller\Notifications\SellerApplicationApprovedNotification;

// Side-effect only. The 'seller' role is NOT assigned here — that already
// happened inside the approve transaction (C3).
final class NotifyUserOfApprovalListener
{
    public function handle(SellerApplicationApproved $event): void
    {
        $event->application->loadMissing('user')->user->notify(
            new SellerApplicationApprovedNotification($event->application->store_name),
        );
    }
}
