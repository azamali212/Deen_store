<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerBankProofReviewed;
use App\Domain\Seller\Notifications\SellerBankProofReviewedNotification;

final class NotifySellerOfBankReviewListener
{
    public function handle(SellerBankProofReviewed $event): void
    {
        $profile = $event->profile->loadMissing('user');

        $profile->user->notify(new SellerBankProofReviewedNotification(
            storeName: $profile->store_name,
            maskedAccount: (string) $profile->maskedBankAccount(),
            verified: $event->verified,
            reason: $event->reason,
        ));
    }
}
