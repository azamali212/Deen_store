<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerStoreNameChangeRequested;
use App\Domain\Seller\Notifications\SellerStoreNameChangeRequestedNotification;
use App\Domain\Seller\Support\SellerReviewerDirectory;
use Illuminate\Support\Facades\Notification;

final readonly class NotifyAdminsOfNameChangeListener
{
    public function __construct(
        private SellerReviewerDirectory $reviewers,
    ) {}

    public function handle(SellerStoreNameChangeRequested $event): void
    {
        Notification::send(
            $this->reviewers->all(),
            new SellerStoreNameChangeRequestedNotification(
                sellerProfileId: (int) $event->profile->id,
                currentName: (string) $event->profile->store_name,
                requestedName: (string) $event->profile->pending_store_name,
            ),
        );
    }
}
