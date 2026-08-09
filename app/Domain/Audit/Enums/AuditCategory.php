<?php

declare(strict_types=1);

namespace App\Domain\Audit\Enums;

enum AuditCategory: string
{
    case AUTHENTICATION = 'authentication';
    case SECURITY = 'security';
    case USER_MANAGEMENT = 'user_management';
    case ACCESS_CONTROL = 'access_control';
    case SELLER_MANAGEMENT = 'seller_management';
    case PRODUCT_MANAGEMENT = 'product_management';
    case ORDER_MANAGEMENT = 'order_management';
    case PAYMENT = 'payment';
    case INVENTORY = 'inventory';
    case SYSTEM = 'system';
}
