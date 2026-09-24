<?php

declare(strict_types=1);

namespace App\Domain\Seller\Support;

use App\Domain\Seller\Contracts\LogoStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class LocalLogoStorage implements LogoStorageInterface
{
    private const DISK = 'public';

    private const DIRECTORY = 'seller-logos';

    public function store(int $sellerProfileId, UploadedFile $file): string
    {
        return $file->store(self::DIRECTORY.'/'.$sellerProfileId, self::DISK);
    }

    public function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
    }

    public function url(string $path): string
    {
        return Storage::disk(self::DISK)->url($path);
    }
}
