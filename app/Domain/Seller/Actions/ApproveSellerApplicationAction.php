<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\ReviewSellerApplicationDTO;
use App\Domain\Seller\Events\SellerApplicationApproved;
use App\Domain\Seller\Services\SellerApplicationService;
use App\Models\SellerApplication;

final readonly class ApproveSellerApplicationAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    public function execute(int $applicationId, ReviewSellerApplicationDTO $dto): SellerApplication
    {
        [$application, $profile] = $this->service->approve($applicationId, $dto);

        // Outside the transaction on purpose (see the event's comment).
        event(new SellerApplicationApproved($application, $profile));

        return $application->load(['user:id,name,email', 'reviewer:id,name,email', 'documents']);
    }
}
