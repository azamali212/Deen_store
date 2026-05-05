<?php

declare(strict_types=1);

namespace App\Domain\Permissions\Enums;

enum SystemRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case PLATFORM_ADMIN = 'platform_admin';
    case SECURITY_ADMIN = 'security_admin';
    case PRODUCT_ADMIN = 'product_admin';
    case CATALOG_ADMIN = 'catalog_admin';
    case INVENTORY_ADMIN = 'inventory_admin';
    case ORDER_ADMIN = 'order_admin';
    case PAYMENT_ADMIN = 'payment_admin';
    case DELIVERY_ADMIN = 'delivery_admin';
    case SUPPORT_ADMIN = 'support_admin';
    case MODERATOR = 'moderator';
    case SELLER = 'seller';
    case SELLER_MANAGER = 'seller_manager';
    case SELLER_STAFF = 'seller_staff';
    case CUSTOMER = 'customer';
}