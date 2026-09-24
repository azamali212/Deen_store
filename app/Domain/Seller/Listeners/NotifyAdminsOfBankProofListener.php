<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerBankProofUploaded;
use App\Domain\Seller\Notifications\SellerBankProofPendingNotification;
use App\Domain\Seller\Support\SellerReviewerDirectory;
use Illuminate\Support\Facades\Notification;

final class NotifyAdminsOfBankProofListener
{
    public function __construct(
        private readonly SellerReviewerDirectory $reviewers,
    ) {}

    public function handle(SellerBankProofUploaded $event): void
    {
        // The AI already matched it — nobody needs to look at it.
        if ($event->autoMatched) {
            return;
        }

        $admins = $this->reviewers->all();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new SellerBankProofPendingNotification(
            sellerProfileId: $event->profile->id,
            storeName: $event->profile->store_name,
            maskedAccount: (string) $event->profile->maskedBankAccount(),
        ));
    }
}
