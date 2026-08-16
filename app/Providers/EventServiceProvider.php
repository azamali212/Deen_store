<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Audit\Listeners\AuditAccountLockedListener;
use App\Domain\Audit\Listeners\AuditAccountUnlockedListener;
use App\Domain\Audit\Listeners\AuditRecoveryCodesRegeneratedListener;
use App\Domain\Audit\Listeners\AuditRecoveryCodeUsedListener;
use App\Domain\Audit\Listeners\AuditTwoFactorDisabledListener;
use App\Domain\Audit\Listeners\AuditTwoFactorEnabledListener;
use App\Domain\Audit\Listeners\AuditTwoFactorVerifiedListener;
use App\Domain\Auth\Events\AccountLocked;
use App\Domain\Auth\Events\AccountUnlocked;
use App\Domain\Auth\Events\EmailVerificationRequested;
use App\Domain\Auth\Events\EmailVerified;
use App\Domain\Auth\Events\LoginFailed;
use App\Domain\Auth\Events\OtpSent;
use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Auth\Events\PasswordHistoryCreated;
use App\Domain\Auth\Events\PasswordResetCompleted;
use App\Domain\Auth\Events\PasswordResetRequested;
use App\Domain\Auth\Events\RecoveryCodesGenerated;
use App\Domain\Auth\Events\RecoveryCodesRegenerated;
use App\Domain\Auth\Events\RecoveryCodeUsed;
use App\Domain\Auth\Events\SessionTerminated;
use App\Domain\Auth\Events\SuspiciousLoginDetected;
use App\Domain\Auth\Events\TrustedDeviceRevoked;
use App\Domain\Auth\Events\TwoFactorDisabled;
use App\Domain\Auth\Events\TwoFactorEnabled;
use App\Domain\Auth\Events\TwoFactorVerified;
use App\Domain\Auth\Events\UserCreated;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Listeners\LogAccountLockedListener;
use App\Domain\Auth\Listeners\LogAccountUnlockedListener;
use App\Domain\Auth\Listeners\LogFailedLoginListener;
use App\Domain\Auth\Listeners\LogPasswordHistoryCreatedListener;
use App\Domain\Auth\Listeners\LogSessionTerminatedListener;
use App\Domain\Auth\Listeners\LogSuccessfulLoginListener;
use App\Domain\Auth\Listeners\LogTrustedDeviceRevokedListener;
use App\Domain\Auth\Listeners\MarkEmailVerifiedListener;
use App\Domain\Auth\Listeners\MarkPasswordResetCompletedListener;
use App\Domain\Auth\Listeners\NotifySuspiciousLoginListener;
use App\Domain\Auth\Listeners\RequestEmailVerificationListener;
use App\Domain\Auth\Listeners\SendAccountLockedNotificationListener;
use App\Domain\Auth\Listeners\SendAccountUnlockedNotificationListener;
use App\Domain\Auth\Listeners\SendOtpNotificationListener;
use App\Domain\Auth\Listeners\SendPasswordChangedNotificationListener;
use App\Domain\Auth\Listeners\SendPasswordResetEmailListener;
use App\Domain\Auth\Listeners\SendRecoveryCodesNotificationListener;
use App\Domain\Auth\Listeners\SendRecoveryCodesRegeneratedNotificationListener;
use App\Domain\Auth\Listeners\SendTwoFactorDisabledNotificationListener;
use App\Domain\Auth\Listeners\SendTwoFactorEnabledNotificationListener;
use App\Domain\Auth\Listeners\SendVerificationEmailListener;
use App\Domain\Auth\Listeners\SendWelcomeEmailListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        UserLoggedIn::class => [
            LogSuccessfulLoginListener::class,
        ],

        LoginFailed::class => [
            LogFailedLoginListener::class,
        ],

        OtpSent::class => [
            SendOtpNotificationListener::class,
        ],

        SuspiciousLoginDetected::class => [
            NotifySuspiciousLoginListener::class,
        ],

        UserCreated::class => [
            SendWelcomeEmailListener::class,
            RequestEmailVerificationListener::class,
        ],

        EmailVerificationRequested::class => [
            SendVerificationEmailListener::class,
        ],

        EmailVerified::class => [
            MarkEmailVerifiedListener::class,
        ],

        PasswordResetRequested::class => [
            SendPasswordResetEmailListener::class,
        ],

        PasswordResetCompleted::class => [
            MarkPasswordResetCompletedListener::class,
        ],

        PasswordChanged::class => [
            SendPasswordChangedNotificationListener::class,
        ],

        SessionTerminated::class => [
            LogSessionTerminatedListener::class,
        ],

        TrustedDeviceRevoked::class => [
            LogTrustedDeviceRevokedListener::class,
        ],

        AccountLocked::class => [
            LogAccountLockedListener::class,
            SendAccountLockedNotificationListener::class,
            AuditAccountLockedListener::class,
        ],

        AccountUnlocked::class => [
            LogAccountUnlockedListener::class,
            SendAccountUnlockedNotificationListener::class,
            AuditAccountUnlockedListener::class,
        ],

        PasswordHistoryCreated::class => [
            LogPasswordHistoryCreatedListener::class,
        ],

        TwoFactorEnabled::class => [

            AuditTwoFactorEnabledListener::class,

            SendTwoFactorEnabledNotificationListener::class,

        ],

        TwoFactorDisabled::class => [

            AuditTwoFactorDisabledListener::class,

            SendTwoFactorDisabledNotificationListener::class,

        ],

        TwoFactorVerified::class => [

            AuditTwoFactorVerifiedListener::class,

        ],

        RecoveryCodeUsed::class => [

            AuditRecoveryCodeUsedListener::class,

        ],

        RecoveryCodesGenerated::class => [

            SendRecoveryCodesNotificationListener::class,

        ],

        RecoveryCodesRegenerated::class => [

            AuditRecoveryCodesRegeneratedListener::class,

            SendRecoveryCodesRegeneratedNotificationListener::class,

        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}