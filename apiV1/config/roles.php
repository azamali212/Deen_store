<?php

return [
    'Super Admin' => [
        'permissions' => 'all',
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
    ],

    'Product Admin' => [
        'permissions' => [
            'Product-index',
            'Product-create',
            'Product-edit',
            'Product-delete',
            'Product-restore',
        ],
    ],

    'Order Admin' => [
        'permissions' => [
            'order-index',
            'order-create',
            'order-edit',
            'order-delete',
            'order-restore',
        ],
    ],
];