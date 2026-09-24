<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Moderation\Actions\EnforceContentModerationAction;
use App\Domain\Seller\Events\SellerProfileUpdated;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;
use Illuminate\Http\UploadedFile;

final readonly class UploadSellerLogoAction
{
    public function __construct(
        private SellerProfileService $service,
        private EnforceContentModerationAction $moderation,
    ) {}

    public function execute(int $userId, UploadedFile $file): SellerProfile
    {
        // C25 — permission and suspension FIRST, before the paid AI call.
        $this->service->guardLogoChange($userId);

        // Checked on the raw bytes before the file is stored — exactly
        // like UploadAvatarAction, a rejected image never touches disk.
        $this->moderation->enforceAvatar(
            $userId,
            (string) file_get_contents($file->getRealPath()),
            $file->getMimeType() ?: 'image/jpeg',
        );

        $profile = $this->service->uploadLogo($userId, $file);

        event(new SellerProfileUpdated($profile, ['logo']));

        return $profile;
    }
}
