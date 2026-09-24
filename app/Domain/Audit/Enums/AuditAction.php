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
    case TWO_FACTOR_CONFIRMED = 'auth.two_factor.confirmed';
    case TWO_FACTOR_CHALLENGED = 'auth.two_factor.challenged';
    case USER_CREATED = 'user.created';
    case USER_UPDATED = 'user.updated';
    case PROFILE_UPDATED = 'user.profile.updated';
    case AVATAR_UPLOADED = 'user.avatar.uploaded';
    case AVATAR_DELETED = 'user.avatar.deleted';
    case PREFERENCES_UPDATED = 'user.preferences.updated';
    case ADDRESS_ADDED = 'user.address.added';
    case ADDRESS_UPDATED = 'user.address.updated';
    case ADDRESS_DELETED = 'user.address.deleted';
    case ADDRESS_DEFAULT_CHANGED = 'user.address.default_changed';
    case PHONE_VERIFIED = 'user.phone.verified';
    case USER_ACTIVATED = 'user.activated';
    case USER_DEACTIVATED = 'user.deactivated';
    case ACCOUNT_DELETED = 'user.account.deleted';
    case ACCOUNT_RESTORED = 'user.account.restored';
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

    case TWO_FACTOR_VERIFIED = 'auth.two_factor.verified';
    case RECOVERY_CODE_USED = 'auth.recovery_code.used';
    case RECOVERY_CODES_GENERATED = 'auth.recovery_codes.generated';
    case RECOVERY_CODES_REGENERATED = 'auth.recovery_codes.regenerated';
    case PROFILE_FLAGGED = 'moderation.profile.flagged';
    case MODERATION_FLAG_RESOLVED = 'moderation.flag.resolved';
    case PROFILE_CONTENT_BLOCKED = 'moderation.profile.content_blocked';
    case DATA_EXPORTED = 'user.data.exported';

    // Seller onboarding (BLUEPRINT.txt section 6)
    case SELLER_DOCUMENT_UPLOADED = 'seller.document.uploaded';
    case SELLER_APPLICATION_SUBMITTED = 'seller.application.submitted';
    case SELLER_APPLICATION_RESUBMITTED = 'seller.application.resubmitted';
    case SELLER_APPLICATION_APPROVED = 'seller.application.approved';
    case SELLER_APPLICATION_REJECTED = 'seller.application.rejected';
    case SELLER_PROFILE_UPDATED = 'seller.profile.updated';
    case SELLER_BANK_DETAILS_CHANGED = 'seller.bank_details.changed';

    // Seller AI verification (BLUEPRINT.txt section 10)
    case SELLER_DOCUMENT_REJECTED_BY_AI = 'seller.document.rejected_by_ai';
    case SELLER_APPLICATION_BLOCKED_BY_AI = 'seller.application.blocked_by_ai';
    case SELLER_BANK_UNVERIFIED = 'seller.bank.unverified';

    // Phase 6 — admin control of live sellers + bank proof
    case SELLER_REACTIVATED = 'seller.reactivated';
    case SELLER_BANK_PROOF_UPLOADED = 'seller.bank.proof_uploaded';
    case SELLER_BANK_VERIFIED_BY_ADMIN = 'seller.bank.verified_by_admin';
    case SELLER_BANK_PROOF_REJECTED = 'seller.bank.proof_rejected';

    // Phase 7 — seller team
    case SELLER_TEAM_MEMBER_INVITED = 'seller.team.member_invited';
    case SELLER_TEAM_MEMBER_JOINED = 'seller.team.member_joined';
    case SELLER_TEAM_ROLE_CHANGED = 'seller.team.role_changed';
    case SELLER_TEAM_MEMBER_REMOVED = 'seller.team.member_removed';

    // Phase 8a — document expiry and re-KYC.
    case SELLER_DOCUMENT_RENEWAL_UPLOADED = 'seller.document.renewal_uploaded';
    case SELLER_DOCUMENT_RENEWAL_APPROVED = 'seller.document.renewal_approved';
    case SELLER_DOCUMENT_RENEWAL_REJECTED = 'seller.document.renewal_rejected';
    case SELLER_KYC_EXPIRING = 'seller.kyc.expiring';
    case SELLER_KYC_EXPIRED = 'seller.kyc.expired';

    // Phase 8b — the seller's own exit. Kept apart from SELLER_SUSPENDED
    // on purpose: who ended the business is the point of the record.
    case SELLER_STORE_CLOSED = 'seller.store.closed';
    case SELLER_STORE_REOPEN_REQUESTED = 'seller.store.reopen_requested';
    case SELLER_STORE_REOPENED = 'seller.store.reopened';

    // Phase 9 — renaming and retention.
    case SELLER_STORE_NAME_CHANGE_REQUESTED = 'seller.store.name_change_requested';
    case SELLER_STORE_RENAMED = 'seller.store.renamed';
    case SELLER_STORE_NAME_CHANGE_REJECTED = 'seller.store.name_change_rejected';
}