<?php

declare(strict_types=1);

namespace App\Domain\Seller\Enums;

enum SellerProfileStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
}
