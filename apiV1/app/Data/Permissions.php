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
        'view categories',
        'create categories',
        'update categories',
        'delete categories',
        'view active categories',
        'view parent categories',
        'view categories with subcategories',
        'search categories',

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
        'view cart',
        'add to cart',
        'update cart',
        'delete cart item',
        'clear cart',
        'apply discount',
        'calculate tax',
        'merge guest cart',
        'move to wishlist',
        'recover abandoned carts',
        'get personalized recommendations',
        'validate stock',
        'apply currency conversion',
        'auto apply best discount',
        'estimate delivery date',
        'handle subscription',
        'save cart state',
        'reorder past cart',
        'handle split payments',

        // Order Management
        'Order-manage',
        'order-index',
        'order-prioritize',
        'order-show',
        'order-create',
        'order-update',
        'order-delete',
        'order-filter',
        'order-filter-by-user',
        'order-filter-by-role-user',
        'order-predict',
        'order-change-status',
        'order-get-delayed',
        'order-mark-delayed',
        'order-escalate',
        'order-escalate-delayed',
        'order-detect-fraud',
        'order-process-automate',

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
        'inventory-manage',
        'inventory-index',
        'inventory-show',
        'inventory-create',
        'inventory-edit',
        'inventory-delete',
        'inventory-log-view',
        'inventory-log-create',
        'inventory-forecast-sales',
        'inventory-auto-restock',
        'inventory-track-batch-expiry',
        'inventory-report-generate',
        'inventory-report-export',
        'inventory-transfer',
        'inventory-stock-level',
        'inventory-allocate-stock-order',
        'inventory-warehouse-stock',

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

        //Email System 
        'send-email',
        'view emails',
        'mark email as read',
        'mark email as unread',
        'archive email',
        'unarchive email',
        'delete email',
        'move email to trash',
        'restore email',
        'empty trash',
        'view trashed emails',
        'create drafts',
        'view drafts',
        'view specific draft',
        'delete drafts',
        'restore drafts',
        'permanently delete drafts',
        'move draft to trash',
        'update drafts status',
        'lock draft',
        'track draft history',
    ];

    public static function getAll(): array
    {
        return self::$permissions;
    }
}
