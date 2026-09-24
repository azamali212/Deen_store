<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerStoreReactivated;
use App\Domain\Seller\Events\SellerStoreSuspended;
use App\Domain\Seller\Notifications\SellerStoreReactivatedNotification;
use App\Domain\Seller\Notifications\SellerStoreSuspendedNotification;

final class NotifySellerOfStoreStatusListener
{
    public function handle(SellerStoreSuspended|SellerStoreReactivated $event): void
    {
        $profile = $event->profile->loadMissing('user');

        $profile->user->notify($event instanceof SellerStoreSuspended
            ? new SellerStoreSuspendedNotification($profile->store_name, $event->reason)
            : new SellerStoreReactivatedNotification($profile->store_name));
    }
}
