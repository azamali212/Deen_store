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

        // Product Category Management
        'product-category-index',
        'product-category-create',
        'product-category-edit',
        'product-category-delete',

        // Product Subcategory Management
        'product-subcategory-index',
        'product-subcategory-create',
        'product-subcategory-edit',
        'product-subcategory-delete',

        // Product Brand Management
        'product-brand-index',
        'product-brand-create',
        'product-brand-edit',
        'product-brand-delete',

        // Product Variant Management
        'product-variant-index',
        'product-variant-create',
        'product-variant-edit',
        'product-variant-delete',

        // Product Image Management
        'product-image-index',
        'product-image-create',
        'product-image-edit',
        'product-image-delete',

        // Product Rating Management
        'product-rating-index',
        'product-rating-create',
        'product-rating-edit',
        'product-rating-delete',

        // Product Inventory Management
        'product-inventory-index',
        'product-inventory-update',
        'product-inventory-delete',

        // Product Discount Management
        'product-discount-index',
        'product-discount-create',
        'product-discount-edit',
        'product-discount-delete',

        // Product Review Management
        'product-review-index',
        'product-review-create',
        'product-review-edit',
        'product-review-delete',

        // Product Tag Management
        'product-tag-index',
        'product-tag-create',
        'product-tag-edit',
        'product-tag-delete',

        // Cart Management
        'cart-index',
        'cart-create',
        'cart-edit',
        'cart-delete',

        // Order Management
        'order-manage',
        'order-status-update',
        'order-index',
        'order-create',
        'order-edit',
        'order-delete',
        'order-restore',

        // Payment Management
        'payment-index',
        'payment-create',
        'payment-edit',
        'payment-delete',

        // Shipping Management
        'shipping-index',
        'shipping-create',
        'shipping-edit',
        'shipping-delete',

        // Delivery Management
        'delivery-index',
        'delivery-create',
        'delivery-edit',
        'delivery-delete',

        // Vendor Management
        'vendor-index',
        'vendor-create',
        'vendor-edit',
        'vendor-delete',

        // Customer Management
        'customer-index',
        'customer-create',
        'customer-edit',
        'customer-delete',

        // Review Management
        'review-index',
        'review-create',
        'review-edit',
        'review-delete',

        // Coupon Management
        'coupon-index',
        'coupon-create',
        'coupon-edit',
        'coupon-delete',

        // Invoice Management
        'invoice-index',
        'invoice-create',
        'invoice-edit',
        'invoice-delete',

        // Refund Management
        'refund-index',
        'refund-create',
        'refund-edit',
        'refund-delete',

        // Inventory Management
        'inventory-index',
        'inventory-create',
        'inventory-edit',
        'inventory-delete',

        // Report Management
        'report-index',
        'report-create',
        'report-edit',
        'report-delete',

        // Settings Management
        'setting-index',
        'setting-create',
        'setting-edit',
        'setting-delete',

        // Wishlist Management
        'wishlist-index',
        'wishlist-create',
        'wishlist-edit',
        'wishlist-delete',

    ];

    public static function getAll(): array
    {
        return self::$permissions;
    }
}