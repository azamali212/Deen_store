<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerStoreReopenRequested;
use App\Domain\Seller\Notifications\SellerStoreReopenRequestedNotification;
use App\Domain\Seller\Support\SellerReviewerDirectory;
use Illuminate\Support\Facades\Notification;

final readonly class NotifyAdminsOfReopenRequestListener
{
    public function __construct(
        private SellerReviewerDirectory $reviewers,
    ) {}

    public function handle(SellerStoreReopenRequested $event): void
    {
        Notification::send(
            $this->reviewers->all(),
            new SellerStoreReopenRequestedNotification(
                sellerProfileId: (int) $event->profile->id,
                storeName: (string) $event->profile->store_name,
                closureReason: $event->profile->closure_reason,
            ),
        );
    }
}
