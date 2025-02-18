<?php

namespace App\Data;

class Permissions
{
    public static array $permissions = [
        // Role and Permission Management
        'role-index',
        'role-show',
        'role-create',
        'role-edit',
        'role-delete',
        'permission-index',
        'permission-show',
        'permission-create',
        'permission-edit',
        'permission-delete',

        // Store Management
        'store-index',
        'store-show',
        'store-create',
        'store-edit',
        'store-delete',

        // User Management
        'user-index',
        'user-show',
        'user-create',
        'user-edit',
        'user-delete',

        // Product Management
        'product-index',
        'product-show',
        'product-create',
        'product-edit',
        'product-delete',
        'product-restore',

        // Product Category Management
        'product-category-index',
        'product-category-show',
        'product-category-create',
        'product-category-edit',
        'product-category-delete',

        // Product Subcategory Management
        'product-subcategory-index',
        'product-subcategory-show',
        'product-subcategory-create',
        'product-subcategory-edit',
        'product-subcategory-delete',

        // Product Brand Management
        'product-brand-index',
        'product-brand-show',
        'product-brand-create',
        'product-brand-edit',
        'product-brand-delete',

        // Product Variant Management
        'product-variant-index',
        'product-variant-show',
        'product-variant-create',
        'product-variant-edit',
        'product-variant-delete',

        // Product Image Management
        'product-image-index',
        'product-image-show',
        'product-image-create',
        'product-image-edit',
        'product-image-delete',

        // Product Rating Management
        'product-rating-index',
        'product-rating-show',
        'product-rating-create',
        'product-rating-edit',
        'product-rating-delete',

        // Product Inventory Management
        'product-inventory-index',
        'product-inventory-show',
        'product-inventory-update',
        'product-inventory-delete',

        // Product Discount Management
        'product-discount-index',
        'product-discount-show',
        'product-discount-create',
        'product-discount-edit',
        'product-discount-delete',

        // Product Review Management
        'product-review-index',
        'product-review-show',
        'product-review-create',
        'product-review-edit',
        'product-review-delete',

        // Product Tag Management
        'product-tag-index',
        'product-tag-show',
        'product-tag-create',
        'product-tag-edit',
        'product-tag-delete',

        // Cart Management
        'cart-index',
        'cart-show',
        'cart-create',
        'cart-edit',
        'cart-delete',

        // Order Management
        'order-manage',
        'order-status-update',
        'order-index',
        'order-show',
        'order-create',
        'order-edit',
        'order-delete',
        'order-restore',

        // Payment Management
        'payment-index',
        'payment-show',
        'payment-create',
        'payment-edit',
        'payment-delete',

        // Shipping Management
        'shipping-index',
        'shipping-show',
        'shipping-create',
        'shipping-edit',
        'shipping-delete',

        // Delivery Management
        'delivery-index',
        'delivery-show',
        'delivery-create',
        'delivery-edit',
        'delivery-delete',

        // Vendor Management
        'vendor-index',
        'vendor-show',
        'vendor-create',
        'vendor-edit',
        'vendor-delete',

        // Customer Management
        'customer-index',
        'customer-show',
        'customer-create',
        'customer-edit',
        'customer-delete',

        // Review Management
        'review-index',
        'review-show',
        'review-create',
        'review-edit',
        'review-delete',

        // Coupon Management
        'coupon-index',
        'coupon-show',
        'coupon-create',
        'coupon-edit',
        'coupon-delete',

        // Invoice Management
        'invoice-index',
        'invoice-show',
        'invoice-create',
        'invoice-edit',
        'invoice-delete',

        // Refund Management
        'refund-index',
        'refund-show',
        'refund-create',
        'refund-edit',
        'refund-delete',

        // Inventory Management
        'inventory-index',
        'inventory-show',
        'inventory-create',
        'inventory-edit',
        'inventory-delete',

        // Report Management
        'report-index',
        'report-show',
        'report-create',
        'report-edit',
        'report-delete',

        // Settings Management
        'setting-index',
        'setting-show',
        'setting-create',
        'setting-edit',
        'setting-delete',

        // Wishlist Management
        'wishlist-index',
        'wishlist-show',
        'wishlist-create',
        'wishlist-edit',
        'wishlist-delete',
    ];

    public static function getAll(): array
    {
        return self::$permissions;
    }
}