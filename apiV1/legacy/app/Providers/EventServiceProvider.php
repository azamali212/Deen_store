<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Events\LoginOtpSent;
use App\Listeners\Notification\NotifyLoginOtpSent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        LoginOtpSent::class => [
            NotifyLoginOtpSent::class,
        ],
    ];
}