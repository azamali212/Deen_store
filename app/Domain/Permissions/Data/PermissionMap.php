<?php

declare(strict_types=1);

namespace App\Domain\Permissions\Data;

use App\Domain\Permissions\Enums\PermissionAction;

final class PermissionMap
{
    public const GUARD = 'api';

    //Create Main Permissions for each module and then you can use them in RolePermissionMap to assign to roles, you can also use only, except, exact and merge methods to create custom permission sets for specific roles if needed
    public static function modules(): array
    {
        return [
            'panel.admin' => [PermissionAction::ACCESS],
            'panel.seller' => [PermissionAction::ACCESS],
            'panel.customer' => [PermissionAction::ACCESS],

            'security.sessions' => [PermissionAction::VIEW, PermissionAction::REVOKE],
            'security.login_logs' => [PermissionAction::VIEW],
            'security.audit_logs' => [PermissionAction::VIEW],
            'security.risk' => [PermissionAction::VIEW, PermissionAction::REVIEW, PermissionAction::MANAGE],
            'security.step_up' => [PermissionAction::MANAGE],
            'security.trusted_devices' => [PermissionAction::VIEW, PermissionAction::REVOKE, PermissionAction::MANAGE],

            'users.customers' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DEACTIVATE, PermissionAction::DELETE],
            'users.admins' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DEACTIVATE, PermissionAction::DELETE],
            'users.sellers' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::APPROVE, PermissionAction::SUSPEND],

            'access.roles' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],
            'access.permissions' => [PermissionAction::VIEW, PermissionAction::MANAGE],

            'catalog.products' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::APPROVE, PermissionAction::MANAGE],
            'catalog.categories' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],
            'catalog.brands' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],
            'catalog.variants' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],
            'catalog.media' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::DELETE, PermissionAction::MANAGE],

            'inventory.stock' => [PermissionAction::VIEW, PermissionAction::ADJUST, PermissionAction::TRANSFER, PermissionAction::MANAGE],
            'inventory.warehouses' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],

            'orders.orders' => [PermissionAction::VIEW, PermissionAction::UPDATE, PermissionAction::CANCEL, PermissionAction::REFUND, PermissionAction::ESCALATE, PermissionAction::EXPORT],
            'orders.returns' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::REFUND, PermissionAction::MANAGE],
            'orders.refunds' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::MANAGE],

            'payments.transactions' => [PermissionAction::VIEW, PermissionAction::EXPORT],
            'payments.refunds' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::MANAGE],
            'payments.payouts' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::MANAGE],

            'shipping.shipments' => [PermissionAction::VIEW, PermissionAction::UPDATE, PermissionAction::ASSIGN, PermissionAction::MANAGE],
            'shipping.methods' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],
            'shipping.zones' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],

            'marketplace.stores' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::APPROVE, PermissionAction::SUSPEND, PermissionAction::MANAGE],
            'marketplace.sellers' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::SUSPEND, PermissionAction::MANAGE],
            'marketplace.payouts' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::MANAGE],

            'support.tickets' => [PermissionAction::VIEW, PermissionAction::REPLY, PermissionAction::ESCALATE, PermissionAction::MANAGE],
            'support.disputes' => [PermissionAction::VIEW, PermissionAction::REPLY, PermissionAction::ESCALATE, PermissionAction::MANAGE],

            'moderation.reviews' => [PermissionAction::VIEW, PermissionAction::APPROVE, PermissionAction::REJECT, PermissionAction::MODERATE],
            'moderation.social_posts' => [PermissionAction::VIEW, PermissionAction::MODERATE],
            'moderation.resell_listings' => [PermissionAction::VIEW, PermissionAction::MODERATE],

            'notifications.notifications' => [PermissionAction::VIEW, 'send', PermissionAction::MANAGE],
            'notifications.templates' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],
            'notifications.preferences' => [PermissionAction::VIEW, PermissionAction::UPDATE, PermissionAction::MANAGE],

            'email.mailbox' => [PermissionAction::VIEW, 'send', PermissionAction::REPLY, PermissionAction::DELETE, PermissionAction::MANAGE],
            'email.drafts' => [PermissionAction::VIEW, PermissionAction::CREATE, PermissionAction::UPDATE, PermissionAction::DELETE, PermissionAction::MANAGE],

            'ai.auth_risk' => [PermissionAction::VIEW, PermissionAction::REVIEW, PermissionAction::MANAGE],
            'ai.content' => [PermissionAction::GENERATE, PermissionAction::REVIEW, PermissionAction::MANAGE],
            'ai.moderation' => [PermissionAction::VIEW, PermissionAction::REVIEW, PermissionAction::MANAGE],
            'ai.recommendations' => [PermissionAction::VIEW, PermissionAction::MANAGE],
            'ai.settings' => [PermissionAction::VIEW, PermissionAction::UPDATE, PermissionAction::MANAGE],

            'reports.reports' => [PermissionAction::VIEW, PermissionAction::EXPORT],
            'analytics.analytics' => [PermissionAction::VIEW, PermissionAction::EXPORT],

            'system.settings' => [PermissionAction::VIEW, PermissionAction::UPDATE, PermissionAction::MANAGE],
            'system.maintenance' => [PermissionAction::VIEW, PermissionAction::MANAGE],
        ];
    }

    // This method generates the full list of permissions in the format "module.action" based on the modules and their associated actions defined in the modules() method. It ensures that all permissions are unique and returns them as a flat array.
    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::modules() as $module => $actions) {
            foreach ($actions as $action) {
                $actionValue = $action instanceof PermissionAction ? $action->value : (string) $action;
                $permissions[] = "{$module}.{$actionValue}";
            }
        }

        return array_values(array_unique($permissions));
    }

    // The only method filters the full list of permissions to include only those that match any of the provided patterns. A pattern can be an exact permission name or a prefix followed by a wildcard (e.g., "catalog.*" to match all permissions starting with "catalog."). The except method does the opposite, excluding permissions that match the patterns. The exact method returns only the permissions that exactly match the provided list. The merge method combines multiple permission groups into a single array of unique permissions.
    public static function only(array $patterns): array
    {
        return array_values(array_filter(
            self::permissions(),
            static fn (string $permission): bool => self::matchesAny($permission, $patterns)
        ));
    }

    // The except method filters the full list of permissions to exclude those that match any of the provided patterns.
    public static function except(array $patterns): array
    {
        return array_values(array_filter(
            self::permissions(),
            //this is function use for filtering the permissions based on the patterns provided, they check bool ture and fasle and return the permissions that match the patterns, if the 
            static fn (string $permission): bool => ! self::matchesAny($permission, $patterns)
        ));
    }

    public static function exact(array $permissions): array
    {
        return array_values(array_intersect(self::permissions(), $permissions));
    }

    public static function merge(array ...$permissionGroups): array
    {
        return array_values(array_unique(array_merge(...$permissionGroups)));
    }

    private static function matchesAny(string $permission, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = trim((string) $pattern);

            if ($pattern === '') {
                continue;
            }

            if ($permission === $pattern || str_starts_with($permission, $pattern . '.')) {
                return true;
            }
        }

        return false;
    }
}