<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\DTO\ResolveModerationFlagDTO;
use App\Domain\Moderation\Events\ModerationFlagResolved;
use App\Domain\Moderation\Exceptions\ModerationFlagAlreadyResolvedException;
use App\Models\ModerationFlag;

final readonly class ResolveModerationFlagAction
{
    public function execute(ModerationFlag $flag, ResolveModerationFlagDTO $dto, int $reviewerId): ModerationFlag
    {
        if ($flag->status->isResolved()) {
            throw ModerationFlagAlreadyResolvedException::forFlag($flag->id);
        }

        $flag->update([
            'status' => $dto->status->value,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'review_notes' => $dto->notes,
        ]);

        $flag = $flag->fresh();

        event(new ModerationFlagResolved($flag));

        return $flag;
    }
}
