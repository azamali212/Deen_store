<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Events\SellerKycStatusChanged;
use App\Domain\Seller\Notifications\SellerKycStatusNotification;

final class NotifySellerOfKycStatusListener
{
    public function handle(SellerKycStatusChanged $event): void
    {
        // Going back to VALID needs no warning email — the renewal
        // decision email already said so.
        if ($event->current === SellerKycStatus::VALID) {
            return;
        }

        $owner = $event->profile->user;

        if ($owner === null) {
            return;
        }

        $owner->notify(new SellerKycStatusNotification(
            storeName: (string) $event->profile->store_name,
            status: $event->current,
            expiresOn: $event->profile->earliestKycExpiry()?->toDateString(),
        ));
    }
}
