<?php

declare(strict_types=1);

namespace App\Domain\Auth\Support;

use App\Models\User;
use App\Domain\Auth\Enums\AuthPanel;

final class TokenAbilityBuilder
{
    public function build(
        User $user,
        AuthPanel $panel
    ): array {
        if ($user->hasRole('super_admin')) {
            return ['*'];
        }

        return match ($panel) {

            AuthPanel::ADMIN
                => $this->buildAdminAbilities($user),

            AuthPanel::SELLER
                => $this->buildSellerAbilities($user),

            AuthPanel::CUSTOMER
                => $this->buildCustomerAbilities($user),
        };
    }

    private function buildAdminAbilities(User $user): array
    {
        return array_values(
            array_unique(
                array_merge(
                    ['panel.admin.access'],
                    $user->getAllPermissions()
                        ->pluck('name')
                        ->toArray()
                )
            )
        );
    }

    private function buildSellerAbilities(User $user): array
    {
        return array_values(
            array_unique(
                array_merge(
                    [
                        'panel.seller.access',
                        'seller.profile.view',
                        'seller.profile.update',
                    ],
                    $user->getAllPermissions()
                        ->pluck('name')
                        ->toArray()
                )
            )
        );
    }

    private function buildCustomerAbilities(User $user): array
    {
        return array_values(
            array_unique(
                array_merge(
                    [
                        'panel.customer.access',
                        'customer.profile.view',
                        'customer.profile.update',
                    ],
                    $user->getAllPermissions()
                        ->pluck('name')
                        ->toArray()
                )
            )
        );
    }

    public function createTokenName(
        AuthPanel $panel
    ): string {
        return sprintf(
            '%s-%s',
            $panel->value,
            now()->timestamp
        );
    }
}