<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Domain\Seller\Enums\DocumentRejectionCategory;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Exceptions\DomainException;

// 422 — upload blocked at the door (P5-1). The file was never stored.
final class DocumentRejectedByAiException extends DomainException
{
    public static function forCategory(SellerDocumentType $type, DocumentRejectionCategory $category): self
    {
        return (new self($category->customerMessage($type)))
            ->withContext([
                'document_type' => $type->value,
                'reason' => $category->value,
            ]);
    }
}
