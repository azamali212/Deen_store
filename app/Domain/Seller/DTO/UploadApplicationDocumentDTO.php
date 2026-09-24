<?php

declare(strict_types=1);

namespace App\Domain\Seller\DTO;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Support\Concerns\HasDtoHelpers;
use Illuminate\Http\UploadedFile;

final readonly class UploadApplicationDocumentDTO
{
    use HasDtoHelpers;

    public function __construct(
        public SellerDocumentType $documentType,
        public UploadedFile $file,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            documentType: SellerDocumentType::from(self::requiredString($data, 'document_type')),
            file: $data['file'],
        );
    }
}
