<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Enums\AuthPanel;
use App\Domain\Auth\Exceptions\PanelAccessDeniedException;
use App\Models\User;

final class PanelAccessService
{
    //$this method use for call current object from canAccess method these type of method called method compsition 
    public function ensureCanAccess(User $user, AuthPanel $panel): void
    {
        if (! $this->canAccess($user, $panel)) {
            throw new PanelAccessDeniedException($panel->value);
        }
    }

    public function canAccess(User $user, AuthPanel $panel): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can($panel->accessPermission());
    }

    public function accessiblePanels(User $user): array
    {
        if ($user->hasRole('super_admin')) {
            return array_map(
                static fn (AuthPanel $panel): string => $panel->value,
                AuthPanel::cases()
            );
        }

        return array_values(array_filter(
            array_map(
                static fn (AuthPanel $panel): ?string => $user->can($panel->accessPermission())
                    ? $panel->value
                    : null,
                AuthPanel::cases()
            )
        ));
    }
}