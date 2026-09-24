<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\SellerApplicationStatus;
use App\Domain\Seller\Events\SellerApplicationResubmitted;
use App\Domain\Seller\Events\SellerApplicationSubmitted;
use App\Domain\Seller\Services\SellerApplicationService;
use App\Models\SellerApplication;
use App\Models\User;

/**
 * One endpoint for both first submit and resubmit — the only difference is
 * which event fires, decided by the status BEFORE the change.
 */
final readonly class SubmitSellerApplicationAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    public function execute(User $user): SellerApplication
    {
        $application = $this->service->getForUser($user->id);

        $isResubmission = $application->status === SellerApplicationStatus::REJECTED;

        $application = $this->service->submit($user, $application);

        event($isResubmission
            ? new SellerApplicationResubmitted($application)
            : new SellerApplicationSubmitted($application));

        return $application->load('documents');
    }
}
