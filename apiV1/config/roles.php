<?php

return [
    'Super Admin' => [
        'permissions' => 'all',
        'guard_name' => 'api',
    ],

    'Admin' => [
        'permissions' => [
            'role-index',
            'role-create',
            'role-edit',
            'role-delete',
            'Product-index',
            'Product-create',
            'Product-edit',
            'Product-delete',
            'Product-restore',
            'order-index',
            'order-create',
            'order-edit',
            'order-delete',
            'order-restore',
        ],
        'guard_name' => 'api',
    ],

    'Vendor Admin' => [
        'permissions' => [
            'Product-index',
            'Product-create',
            'Product-edit',
            'Product-delete',
            'Product-restore',
            'order-index',
            'order-create',
            'order-edit',
            'order-delete',
            'order-restore',
            'inventory-index',
            'inventory-create',
            'inventory-edit',
            'inventory-delete',
        ],
        'guard_name' => 'api',
    ],

    'Customer' => [
        'permissions' => [
            'order-index',
            'order-create',
            'order-edit',
            'order-delete',
            'order-restore',
            'wishlist-create',
        ],
        'guard_name' => 'api',
    ],

    'Delivery Manager' => [
        'permissions' => [
            'order-index',
            'order-create',
            'order-edit',
            'order-delete',
            'order-restore',
            'delivery-index',
            'delivery-create',
            'delivery-edit',
            'delivery-delete',
        ],
    ],

    'Store Admin' => [
        'permissions' => [
            'role-index',
            'role-create',
            'role-edit',
            'role-delete',
        ],
        'inherits' => ['Product Admin', 'Order Admin'],
        'guard_name' => 'api',
    ],

    'Product Admin' => [
        'permissions' => [
            'Product-index',
            'Product-create',
            'Product-edit',
            'Product-delete',
            'Product-restore',
        ],
        'guard_name' => 'api',
    ],

    'Order Admin' => [
        'permissions' => [
            'order-index',
            'order-create',
            'order-edit',
            'order-delete',
            'order-restore',
        ],
        'guard_name' => 'api',
    ],
];