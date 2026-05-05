<?php

declare(strict_types=1);

namespace App\Domain\Permissions\Data;

use App\Domain\Permissions\Enums\SystemRole;

final class RolePermissionMap
{
    public static function roles(): array
    {
        return [
            SystemRole::SUPER_ADMIN->value => PermissionMap::permissions(),

            SystemRole::PLATFORM_ADMIN->value => PermissionMap::except([
                'access.permissions',
                'access.roles.delete',
                'security',
                'system.maintenance',
                'ai.settings',
            ]),

            SystemRole::SECURITY_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'security',
                'ai.auth_risk',
                'reports',
            ]),

            SystemRole::PRODUCT_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'catalog.products',
                'catalog.media',
                'ai.content',
            ]),

            SystemRole::CATALOG_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'catalog.categories',
                'catalog.brands',
                'catalog.variants',
                'catalog.media',
            ]),

            SystemRole::INVENTORY_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'inventory',
                'catalog.products',
            ]),

            SystemRole::ORDER_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'orders',
                'payments.transactions',
                'shipping.shipments',
                'support.tickets',
                'email.mailbox',
            ]),

            SystemRole::PAYMENT_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'payments',
                'orders.refunds',
                'reports',
            ]),

            SystemRole::DELIVERY_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'shipping',
                'orders.orders',
                'inventory.stock',
            ]),

            SystemRole::SUPPORT_ADMIN->value => PermissionMap::only([
                'panel.admin',
                'users.customers',
                'orders.orders',
                'support',
                'email.mailbox',
                'notifications.notifications',
            ]),

            SystemRole::MODERATOR->value => PermissionMap::only([
                'panel.admin',
                'moderation',
                'ai.moderation',
            ]),

            SystemRole::SELLER->value => PermissionMap::only([
                'panel.seller',
                'marketplace.stores',
                'catalog.products',
                'catalog.media',
                'inventory.stock',
                'orders.orders',
                'shipping.shipments',
                'payments.payouts',
                'email.mailbox',
                'notifications.notifications',
            ]),

            SystemRole::SELLER_MANAGER->value => PermissionMap::only([
                'panel.seller',
                'marketplace.stores',
                'catalog.products',
                'catalog.media',
                'inventory.stock',
                'orders.orders',
                'shipping.shipments',
                'email.mailbox',
                'notifications.notifications',
            ]),

            SystemRole::SELLER_STAFF->value => [
                'panel.seller.access',
                'marketplace.stores.view',
                'catalog.products.view',
                'catalog.products.create',
                'catalog.products.update',
                'catalog.media.view',
                'catalog.media.create',
                'inventory.stock.view',
                'orders.orders.view',
                'notifications.notifications.view',
            ],

            SystemRole::CUSTOMER->value => [
                'panel.customer.access',
                'notifications.notifications.view',
                'email.mailbox.view',
            ],
        ];
    }
}