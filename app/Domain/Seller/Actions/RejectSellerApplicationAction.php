<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\DTO\ReviewSellerApplicationDTO;
use App\Domain\Seller\Events\SellerApplicationRejected;
use App\Domain\Seller\Services\SellerApplicationService;
use App\Models\SellerApplication;

final readonly class RejectSellerApplicationAction
{
    public function __construct(
        private SellerApplicationService $service,
    ) {}

    public function execute(int $applicationId, ReviewSellerApplicationDTO $dto): SellerApplication
    {
        $application = $this->service->reject($applicationId, $dto);

        event(new SellerApplicationRejected($application));

        return $application->load(['user:id,name,email', 'reviewer:id,name,email', 'documents']);
    }
}
