<?php

declare(strict_types=1);

namespace App\Domain\User\Support;

use App\Domain\User\Contracts\AvatarStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class LocalAvatarStorage implements AvatarStorageInterface
{
    private const DISK = 'public';

    private const DIRECTORY = 'avatars';

    public function store(int $userId, UploadedFile $file): string
    {
        return $file->store(self::DIRECTORY.'/'.$userId, self::DISK);
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
