<?php

declare(strict_types=1);

namespace App\Domain\Audit\Enums;

enum AuditAction: string
{
    case LOGIN_SUCCEEDED = 'auth.login.succeeded';
    case LOGIN_FAILED = 'auth.login.failed';
    case LOGOUT = 'auth.logout';
    case OTP_SENT = 'auth.otp.sent';
    case OTP_VERIFIED = 'auth.otp.verified';
    case EMAIL_VERIFIED = 'auth.email.verified';
    case PASSWORD_RESET_REQUESTED = 'auth.password_reset.requested';
    case PASSWORD_RESET_COMPLETED = 'auth.password_reset.completed';
    case PASSWORD_CHANGED = 'auth.password.changed';
    case SESSION_TERMINATED = 'auth.session.terminated';
    case OTHER_SESSIONS_TERMINATED = 'auth.sessions.others_terminated';
    case TRUSTED_DEVICE_CREATED = 'auth.trusted_device.created';
    case TRUSTED_DEVICE_REVOKED = 'auth.trusted_device.revoked';
    case ACCOUNT_LOCKED = 'auth.account.locked';
    case ACCOUNT_UNLOCKED = 'auth.account.unlocked';
    case TWO_FACTOR_ENABLED = 'auth.two_factor.enabled';
    case TWO_FACTOR_DISABLED = 'auth.two_factor.disabled';
    case TWO_FACTOR_CHALLENGED = 'auth.two_factor.challenged';
    case USER_CREATED = 'user.created';
    case USER_UPDATED = 'user.updated';
    case USER_ACTIVATED = 'user.activated';
    case USER_DEACTIVATED = 'user.deactivated';
    case ROLE_ASSIGNED = 'permission.role.assigned';
    case ROLE_REMOVED = 'permission.role.removed';
    case PERMISSION_GRANTED = 'permission.granted';
    case PERMISSION_REVOKED = 'permission.revoked';
    case PRODUCT_CREATED = 'product.created';
    case PRODUCT_UPDATED = 'product.updated';
    case PRODUCT_DELETED = 'product.deleted';
    case ORDER_CANCELLED = 'order.cancelled';
    case ORDER_REFUNDED = 'order.refunded';
    case PAYMENT_REFUNDED = 'payment.refunded';
    case SELLER_SUSPENDED = 'seller.suspended';
}
