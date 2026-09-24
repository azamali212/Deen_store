<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerStoreNameChangeReviewed;
use App\Domain\Seller\Notifications\SellerStoreNameChangeReviewedNotification;

final class NotifySellerOfNameChangeListener
{
    public function handle(SellerStoreNameChangeReviewed $event): void
    {
        $owner = $event->profile->loadMissing('user')->user;

        $owner?->notify(new SellerStoreNameChangeReviewedNotification(
            previousName: $event->previousName,
            currentName: (string) $event->profile->store_name,
            approved: $event->approved,
            reason: $event->reason,
        ));
    }
}
