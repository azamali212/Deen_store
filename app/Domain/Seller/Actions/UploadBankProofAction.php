<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Events\SellerBankProofUploaded;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;
use Illuminate\Http\UploadedFile;

final readonly class UploadBankProofAction
{
    public function __construct(
        private SellerProfileService $service,
    ) {}

    public function execute(int $userId, UploadedFile $file): SellerProfile
    {
        $result = $this->service->uploadBankProof($userId, $file);

        event(new SellerBankProofUploaded($result['profile'], $result['auto_matched']));

        return $result['profile'];
    }
}
