<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerDocumentRenewalUploaded;
use App\Domain\Seller\Notifications\SellerDocumentRenewalSubmittedNotification;
use App\Domain\Seller\Support\SellerReviewerDirectory;
use Illuminate\Support\Facades\Notification;

final readonly class NotifyAdminsOfDocumentRenewalListener
{
    public function __construct(
        private SellerReviewerDirectory $reviewers,
    ) {}

    public function handle(SellerDocumentRenewalUploaded $event): void
    {
        $renewal = $event->renewal;
        $profile = $renewal->sellerProfile;

        Notification::send(
            $this->reviewers->all(),
            new SellerDocumentRenewalSubmittedNotification(
                renewalId: (int) $renewal->id,
                storeName: (string) $profile->store_name,
                documentLabel: $renewal->document_type->label(),
                // Tells the reviewer this one is holding up someone's money.
                payoutsFrozen: $profile->kycStatus()->blocksPayout(),
            ),
        );
    }
}
