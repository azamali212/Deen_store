<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerStoreClosed;
use App\Domain\Seller\Events\SellerStoreReopened;
use App\Domain\Seller\Notifications\SellerStoreClosedNotification;
use App\Domain\Seller\Notifications\SellerStoreReopenedNotification;

final class NotifySellerOfClosureListener
{
    public function handle(SellerStoreClosed|SellerStoreReopened $event): void
    {
        $profile = $event->profile->loadMissing('user');

        $profile->user?->notify($event instanceof SellerStoreClosed
            ? new SellerStoreClosedNotification((string) $profile->store_name)
            : new SellerStoreReopenedNotification((string) $profile->store_name));
    }
}
