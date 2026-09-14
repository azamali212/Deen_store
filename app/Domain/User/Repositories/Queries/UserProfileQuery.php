<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories\Queries;

use App\Domain\User\Enums\ProfileVisibility;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;

final class UserProfileQuery
{
    public function byUserId(int $userId): Builder
    {
        return UserProfile::query()
            ->where('user_id', $userId);
    }

    public function byUsername(string $username): Builder
    {
        return UserProfile::query()
            ->where('username', $username);
    }

    public function publicProfiles(): Builder
    {
        return UserProfile::query()
            ->where('profile_visibility', ProfileVisibility::PUBLIC->value);
    }

    public function incompleteProfiles(int $threshold = 100): Builder
    {
        return UserProfile::query()
            ->where('profile_completion', '<', $threshold);
    }
}
