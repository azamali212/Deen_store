<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use Illuminate\Http\UploadedFile;

interface AvatarStorageInterface
{
    public function store(int $userId, UploadedFile $file): string;

    public function delete(string $path): void;

    public function url(string $path): string;
}
