<?php

declare(strict_types=1);

namespace App\Domain\Auth\Policies;

use App\Models\User;

final class AuthPolicy
{
    public function view(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user): bool
    {
        return $user->isActive();
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function registerAdmin(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }

    public function registerSeller(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }

    public function manageUsers(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }

    public function manageRoles(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function managePermissions(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}