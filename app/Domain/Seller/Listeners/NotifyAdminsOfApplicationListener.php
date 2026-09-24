<?php

declare(strict_types=1);

namespace App\Domain\Seller\Listeners;

use App\Domain\Seller\Events\SellerApplicationResubmitted;
use App\Domain\Seller\Events\SellerApplicationSubmitted;
use App\Domain\Seller\Notifications\SellerApplicationSubmittedNotification;
use App\Domain\Seller\Support\SellerReviewerDirectory;
use Illuminate\Support\Facades\Notification;

/**
 * Q3: every super_admin + platform_admin — the same roles that can review
 * (D10). Handles both first submit and resubmit.
 */
final class NotifyAdminsOfApplicationListener
{
    public function __construct(
        private readonly SellerReviewerDirectory $reviewers,
    ) {}

    public function handle(SellerApplicationSubmitted|SellerApplicationResubmitted $event): void
    {
        $admins = $this->reviewers->all();

        if ($admins->isEmpty()) {
            return;
        }

        $application = $event->application->loadMissing('user');

        Notification::send($admins, new SellerApplicationSubmittedNotification(
            applicationId: $application->id,
            storeName: $application->store_name,
            businessName: $application->business_name,
            applicantEmail: $application->user->email,
            isResubmission: $event instanceof SellerApplicationResubmitted,
        ));
    }
}
