<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\CaptchaServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    HorizonServiceProvider::class,
    RouteServiceProvider::class,
    TelescopeServiceProvider::class,
    AuditServiceProvider::class,
    CaptchaServiceProvider::class,
];
