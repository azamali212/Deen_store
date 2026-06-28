<?php

declare(strict_types=1);

namespace App\Domain\Auth\Enums;

// This enum is used to define different authentication panels for different user roles in the application. Each panel has its own access permission and token name.
enum AuthPanel: string
{
    case CUSTOMER = 'customer';
    case SELLER = 'seller';
    case ADMIN = 'admin';

    public function accessPermission(): string
{
    return match ($this) {
        self::CUSTOMER => 'panel.customer.access',
        self::SELLER   => 'panel.seller.access',
        self::ADMIN    => 'panel.admin.access',
    };
}

    public function tokenName(): string

    {
        return match ($this) {

            self::CUSTOMER => 'customer-token',
            self::SELLER => 'seller-token',
            self::ADMIN => 'admin-token',
        };
    }
}
