<?php

declare(strict_types=1);

namespace App\Domain\Seller\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Store logos ARE public (they appear on the storefront), so unlike
 * DocumentStorageInterface this one has url(). Same swappable pattern as
 * AvatarStorageInterface — local disk now, S3/CDN later.
 */
interface LogoStorageInterface
{
    public function store(int $sellerProfileId, UploadedFile $file): string;

    public function delete(string $path): void;

    public function url(string $path): string;
}
