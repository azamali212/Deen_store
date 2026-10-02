<?php

declare(strict_types=1);

namespace App\Domain\Seller\Exceptions;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Exceptions\DomainException;

// 422 — A3: this country never asks for that document.
final class DocumentTypeNotAcceptedException extends DomainException
{
    public static function forCountry(SellerDocumentType $type, string $country): self
    {
        return (new self(sprintf('A %s is not one of the documents we ask for in your country.', $type->label())))
            ->withContext(['document_type' => $type->value, 'country' => $country]);
    }
}
