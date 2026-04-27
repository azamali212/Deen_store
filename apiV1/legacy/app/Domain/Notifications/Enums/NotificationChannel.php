<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Enums;

enum NotificationChannel: string
{
    case DATABASE = 'database';
    case BROADCAST = 'broadcast';
    case MAIL = 'mail';
    case SMS = 'sms';
}