<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Enums;

enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case SENDING = 'sending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}