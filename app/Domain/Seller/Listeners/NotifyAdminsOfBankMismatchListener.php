<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerBankUnverified;
use App\Domain\Seller\Notifications\SellerBankUnverifiedNotification;
use App\Domain\Seller\Support\SellerReviewerDirectory;
use Illuminate\Support\Facades\Notification;

final class NotifyAdminsOfBankMismatchListener
{
    public function __construct(
        private readonly SellerReviewerDirectory $reviewers,
    ) {}

    public function handle(SellerBankUnverified $event): void
    {
        $admins = $this->reviewers->all();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new SellerBankUnverifiedNotification(
            sellerProfileId: $event->profile->id,
            storeName: $event->profile->store_name,
            maskedAccount: '****'.$event->enteredLast4,
        ));
    }
}
