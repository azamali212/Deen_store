<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerDocumentRenewalReviewed;
use App\Domain\Seller\Notifications\SellerDocumentRenewalReviewedNotification;

final class NotifySellerOfRenewalDecisionListener
{
    public function handle(SellerDocumentRenewalReviewed $event): void
    {
        $renewal = $event->renewal;
        $profile = $renewal->sellerProfile;
        $owner = $profile->user;

        if ($owner === null) {
            return;
        }

        $owner->notify(new SellerDocumentRenewalReviewedNotification(
            storeName: (string) $profile->store_name,
            documentLabel: $renewal->document_type->label(),
            approved: $event->approved,
            reason: $renewal->rejection_reason,
            validUntil: $profile->earliestKycExpiry()?->toDateString(),
        ));
    }
}
