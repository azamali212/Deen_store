<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\Exceptions\InvalidAvatarException;
use App\Domain\User\ValueObjects\AvatarPath;
use App\Support\Concerns\HasDtoHelpers;

final readonly class UpdateAvatarDTO
{
    use HasDtoHelpers;

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        public int $userId,
        public AvatarPath $avatarPath,
        public string $avatarProvider,
    ) {}

    public static function fromArray(array $data): self
    {
        $avatarPath = AvatarPath::from(self::requiredString($data, 'avatar_path'));

        if (! in_array($avatarPath->extension(), self::ALLOWED_EXTENSIONS, true)) {
            throw InvalidAvatarException::invalidExtension($avatarPath->extension());
        }

        return new self(
            userId: self::requiredInt($data, 'user_id'),
            avatarPath: $avatarPath,
            avatarProvider: self::nullableString($data, 'avatar_provider') ?? 'local',
        );
    }
}
