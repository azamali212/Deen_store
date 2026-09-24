<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Events\SellerDocumentRenewalUploaded;
use App\Domain\Seller\Services\SellerDocumentRenewalService;
use App\Models\SellerDocumentRenewal;
use Illuminate\Http\UploadedFile;

final readonly class UploadDocumentRenewalAction
{
    public function __construct(
        private SellerDocumentRenewalService $service,
    ) {}

    public function execute(int $userId, SellerDocumentType $type, UploadedFile $file): SellerDocumentRenewal
    {
        $renewal = $this->service->upload($userId, $type, $file);

        event(new SellerDocumentRenewalUploaded($renewal));

        return $renewal;
    }
}
