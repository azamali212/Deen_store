<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Events\LoginFailed;
use App\Domain\Auth\Events\OtpSent;
use App\Domain\Auth\Events\SuspiciousLoginDetected;
use App\Domain\Auth\Events\UserCreated;
use App\Domain\Auth\Events\UserLoggedIn;
use App\Domain\Auth\Listeners\LogFailedLoginListener;
use App\Domain\Auth\Listeners\LogSuccessfulLoginListener;
use App\Domain\Auth\Listeners\NotifySuspiciousLoginListener;
use App\Domain\Auth\Listeners\SendOtpNotificationListener;
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
            SendWelcomeEmailListener::class
        ],

    ];

    public function boot(): void
    {
        parent::boot();
    }
}