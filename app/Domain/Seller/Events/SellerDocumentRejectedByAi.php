<?php

declare(strict_types=1);

namespace App\Domain\Seller\Events;

use App\Domain\Seller\Enums\DocumentRejectionCategory;
use App\Domain\Seller\Enums\SellerDocumentType;
use Illuminate\Foundation\Events\Dispatchable;

// Plain values — the rejected file was never stored, so there is no model.
final class SellerDocumentRejectedByAi
{
    use Dispatchable;

    public function __construct(
        public readonly int $applicationId,
        public readonly int $userId,
        public readonly SellerDocumentType $documentType,
        public readonly DocumentRejectionCategory $category,
    ) {}
}
