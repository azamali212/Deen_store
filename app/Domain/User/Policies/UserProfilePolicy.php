<?php

declare(strict_types=1);

namespace App\Domain\User\Policies;

use App\Models\User;
use App\Models\UserProfile;
use App\Domain\User\Enums\ProfileVisibility;

/**
 * Governs who can VIEW a profile that isn't their own, based on its
 * `profile_visibility` setting (public / private / followers).
 *
 * NOT named UserPolicy: App\Models\User already has a registered policy —
 * Auth\Policies\AuthPolicy (see AuthServiceProvider::$policies) — and
 * Laravel allows only one policy class per model. Reusing "UserPolicy"
 * here would either silently do nothing or collide with that binding.
 * This policy is registered for UserProfile::class instead, which has no
 * policy yet.
 */
final class UserProfilePolicy
{
    public function view(User $viewer, UserProfile $profile): bool
    {
        if ($viewer->id === $profile->user_id) {
            return true;
        }

        if ($viewer->hasAnyRole(['super_admin', 'platform_admin'])) {
            return true;
        }

        return match ($profile->profile_visibility) {
            ProfileVisibility::PUBLIC => true,
            // No "followers" relationship exists yet in this codebase —
            // treat FOLLOWERS the same as PRIVATE until that feature ships,
            // rather than silently allowing everyone to see it.
            ProfileVisibility::PRIVATE, ProfileVisibility::FOLLOWERS => false,
        };
    }

    public function update(User $user, UserProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
