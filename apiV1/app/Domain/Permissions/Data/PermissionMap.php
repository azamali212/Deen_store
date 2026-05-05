<?php

declare(strict_types=1);

namespace App\Domain\Permissions\Data;

final class PermissionMap //Final to prevent extension and modification of the permission map, ensuring a single source of truth for permissions in the system
{
    public const GUARD = 'api';

    public static function modules(): array //Use Static for better performance and to avoid instantiating the class when not necessary
    {
        return [
            'panel.admin' => ['access'],
            'panel.seller' => ['access'],
            'panel.customer' => ['access'],

            'security.sessions' => ['view', 'revoke'],
            'security.login_logs' => ['view'],
            'security.audit_logs' => ['view'],
            'security.risk' => ['view', 'review', 'manage'],
            'security.step_up' => ['manage'],
            'security.trusted_devices' => ['view', 'revoke', 'manage'],

            'users.customers' => ['view', 'create', 'update', 'deactivate', 'delete'],
            'users.admins' => ['view', 'create', 'update', 'deactivate', 'delete'],
            'users.sellers' => ['view', 'create', 'update', 'approve', 'suspend'],

            'access.roles' => ['view', 'create', 'update', 'delete', 'manage'],
            'access.permissions' => ['view', 'manage'],

            'catalog.products' => ['view', 'create', 'update', 'delete', 'approve', 'manage'],
            'catalog.categories' => ['view', 'create', 'update', 'delete', 'manage'],
            'catalog.brands' => ['view', 'create', 'update', 'delete', 'manage'],
            'catalog.variants' => ['view', 'create', 'update', 'delete', 'manage'],
            'catalog.media' => ['view', 'create', 'delete', 'manage'],

            'inventory.stock' => ['view', 'adjust', 'transfer', 'manage'],
            'inventory.warehouses' => ['view', 'create', 'update', 'delete', 'manage'],

            'orders.orders' => ['view', 'update', 'cancel', 'refund', 'escalate', 'export'],
            'orders.returns' => ['view', 'approve', 'refund', 'manage'],
            'orders.refunds' => ['view', 'approve', 'manage'],

            'payments.transactions' => ['view', 'export'],
            'payments.refunds' => ['view', 'approve', 'manage'],
            'payments.payouts' => ['view', 'approve', 'manage'],

            'shipping.shipments' => ['view', 'update', 'assign', 'manage'],
            'shipping.methods' => ['view', 'create', 'update', 'delete', 'manage'],
            'shipping.zones' => ['view', 'create', 'update', 'delete', 'manage'],

            'marketplace.stores' => ['view', 'create', 'update', 'approve', 'suspend', 'manage_staff'],
            'marketplace.sellers' => ['view', 'approve', 'suspend', 'manage'],
            'marketplace.payouts' => ['view', 'approve', 'manage'],

            'support.tickets' => ['view', 'reply', 'escalate', 'manage'],
            'support.disputes' => ['view', 'reply', 'escalate', 'manage'],

            'moderation.reviews' => ['view', 'approve', 'reject', 'moderate'],
            'moderation.social_posts' => ['view', 'moderate'],
            'moderation.resell_listings' => ['view', 'moderate'],

            'notifications.notifications' => ['view', 'send', 'manage'],
            'notifications.templates' => ['view', 'create', 'update', 'delete', 'manage'],
            'notifications.preferences' => ['view', 'update', 'manage'],

            'email.mailbox' => ['view', 'send', 'reply', 'delete', 'manage'],
            'email.drafts' => ['view', 'create', 'update', 'delete', 'manage'],

            'ai.auth_risk' => ['view', 'review', 'manage'],
            'ai.content' => ['generate', 'review', 'manage'],
            'ai.moderation' => ['view', 'review', 'manage'],
            'ai.recommendations' => ['view', 'manage'],
            'ai.settings' => ['view', 'update', 'manage'],

            'reports.reports' => ['view', 'export'],
            'analytics.analytics' => ['view', 'export'],

            'system.settings' => ['view', 'update', 'manage'],
            'system.maintenance' => ['view', 'manage'],
        ];
    }

    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::modules() as $module => $actions) { //Self keyword means that the method is being called on the same class, allowing for better organization and encapsulation of permission-related logic within the PermissionMap class
            foreach ($actions as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return array_values(array_unique($permissions));
    }

    public static function only(array $modules): array //The Only keyword indicates that this method will return only the permissions that start with the specified module prefixes, allowing for easy filtering of permissions based on module categories
    {
        return array_values(array_filter(
            self::permissions(),
            fn (string $permission): bool => self::startsWithAny($permission, $modules)
        ));
    }

    public static function except(array $modules): array
    {
        return array_values(array_filter(
            self::permissions(),
            fn (string $permission): bool => ! self::startsWithAny($permission, $modules)
        ));
    }

    private static function startsWithAny(string $permission, array $modules): bool
    {
        foreach ($modules as $module) {
            if (str_starts_with($permission, $module . '.')) {
                return true;
            }
        }

        return false;
    }
}