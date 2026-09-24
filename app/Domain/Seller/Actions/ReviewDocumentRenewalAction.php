<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerDocumentRenewalReviewed;
use App\Domain\Seller\Events\SellerKycStatusChanged;
use App\Domain\Seller\Services\SellerDocumentRenewalService;
use App\Models\SellerDocumentRenewal;

final readonly class ReviewDocumentRenewalAction
{
    public function __construct(
        private SellerDocumentRenewalService $service,
    ) {}

    public function execute(int $renewalId, int $adminId, bool $approve, ?string $reason): SellerDocumentRenewal
    {
        $result = $this->service->review($renewalId, $adminId, $approve, $reason);

        // C15 — events fire AFTER the transaction has committed, so no
        // email ever goes out for work that was rolled back.
        event(new SellerDocumentRenewalReviewed($result['renewal'], $approve));

        $current = $result['profile']->kycStatus();

        if ($current !== $result['previous_status']) {
            event(new SellerKycStatusChanged($result['profile'], $result['previous_status'], $current));
        }

        return $result['renewal'];
    }
}
