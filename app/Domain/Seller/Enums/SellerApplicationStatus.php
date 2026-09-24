<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

/**
 * draft -> pending -> approved | rejected, and rejected -> pending again
 * on resubmit (see BLUEPRINT.txt section 1). These helpers are the ONE
 * place the allowed transitions live, so services never hard-code them.
 */
enum SellerApplicationStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    // Customer may edit business info and upload/replace documents.
    public function isEditable(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    // Customer may (re)submit for review.
    public function isSubmittable(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    // Admin may approve or reject.
    public function isReviewable(): bool
    {
        return $this === self::PENDING;
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}
