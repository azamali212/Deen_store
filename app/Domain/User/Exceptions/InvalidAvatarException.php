<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Exceptions\DomainException;

final class InvalidAvatarException extends DomainException
{
    public static function invalidExtension(string $extension): self
    {
        return (new self("Avatar file extension '.{$extension}' is not allowed."))
            ->withContext(['extension' => $extension]);
    }

    public static function tooLarge(int $sizeInKb, int $maxSizeInKb): self
    {
        return (new self("Avatar file size ({$sizeInKb}KB) exceeds the maximum allowed size ({$maxSizeInKb}KB)."))
            ->withContext([
                'size_kb' => $sizeInKb,
                'max_size_kb' => $maxSizeInKb,
            ]);
    }

    public static function uploadFailed(): self
    {
        return new self('Avatar upload failed. Please try again.');
    }
}
