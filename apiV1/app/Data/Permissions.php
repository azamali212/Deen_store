<?php

namespace App\Data;

class Permissions
{
    public static array $permissions = [
        // Role and Permission Management
        'role-index',
        'role-create',
        'role-edit',
        'role-delete',
        'permission-index',
        'permission-create',
        'permission-edit',
        'permission-delete',

        // Store Management
        'store-index',
        'store-create',
        'store-edit',
        'store-delete',

        // User Management
        'user-index',
        'user-create',
        'user-edit',
        'user-delete',

        // Product Management
        'product-index',
        'product-create',
        'product-edit',
        'product-delete',
        'product-restore',

        // Order Management
        'order-manage',
        'order-status-update',
        'order-index',
        'order-create',
        'order-edit',
        'order-delete',
        'order-restore',

        // Category Management
        'category-index',
        'category-create',
        'category-edit',
        'category-delete',

        //Vendor Management
        'vendor-index',
        'vendor-create',
        'vendor-edit',
        'vendor-delete', 

        //Delivery Management
        'delivery-index',
        'delivery-create',
        'delivery-edit',
        'delivery-delete',
        
        //Customer Management
        'customer-index',
        'customer-create',
        'customer-edit',
        'customer-delete',

        //Report Management
        'report-index',
        'report-create',
        'report-edit',
        'report-delete',

        //Settings Management
        'setting-index',
        'setting-create',
        'setting-edit',
        'setting-delete',

        //Shipping Management
        'shipping-index',
        'shipping-create',
        'shipping-edit',
        'shipping-delete',

        //Payment Management
        'payment-index',
        'payment-create',
        'payment-edit',
        'payment-delete',

        //Review Management
        'review-index',
        'review-create',
        'review-edit',
        'review-delete',

        //Coupon Management
        'coupon-index',
        'coupon-create',
        'coupon-edit',
        'coupon-delete',

        //Wishlist Management
        'wishlist-index',
        'wishlist-create',
        'wishlist-edit',
        'wishlist-delete',

        //Cart Management
        'cart-index',
        'cart-create',
        'cart-edit',
        'cart-delete',

        //Invoice Management
        'invoice-index',
        'invoice-create',
        'invoice-edit',
        'invoice-delete',

        //Refund Management
        'refund-index',
        'refund-create',
        'refund-edit',
        'refund-delete',

        //Inventory Management
        'inventory-index',
        'inventory-create',
        'inventory-edit',
        'inventory-delete',

    ];

    public static function getAll(): array
    {
        return self::$permissions;
    }
}