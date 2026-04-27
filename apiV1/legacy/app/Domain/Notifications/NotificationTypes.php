<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class NotificationTypes
{
    // User-related notifications
    public const LOGIN_OTP_SENT = 'login.otp.sent';
    public const LOGIN_ALERT = 'login.alert';
    public const WELCOME = 'user.welcome';
    public const ORDER_DISPATCHED = 'order.dispatched';
    public const ORDER_DELETED = 'order.deleted';
    public const PROFILE_UPDATED = 'user.profile.updated';

    // Role management notifications
    public const ROLE_REQUESTED = 'role.requested';
    public const ROLE_APPROVED  = 'role.approved';
    public const ROLE_REJECTED  = 'role.rejected';
    private function __construct() {}
}