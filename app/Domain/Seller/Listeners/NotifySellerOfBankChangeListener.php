<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerBankDetailsChanged;
use App\Domain\Seller\Notifications\SellerBankDetailsChangedNotification;

final class NotifySellerOfBankChangeListener
{
    public function handle(SellerBankDetailsChanged $event): void
    {
        $profile = $event->profile->loadMissing('user');

        $profile->user->notify(new SellerBankDetailsChangedNotification(
            storeName: $profile->store_name,
            maskedAccount: '****'.$event->newLast4,
            bankName: $event->newBankName,
            isFirstTime: $event->isFirstTime,
        ));
    }
}
