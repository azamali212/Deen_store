<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Actions;

use App\Domain\Moderation\Enums\ModerationStatus;
use App\Models\ModerationFlag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListModerationFlagsAction
{
    public function execute(?ModerationStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        return ModerationFlag::query()
            ->with(['user:id,email', 'reviewer:id,email'])
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->latest()
            ->paginate($perPage);
    }
}
