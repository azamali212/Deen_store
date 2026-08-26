<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Captcha\Contracts\CaptchaProviderInterface;
use App\Domain\Captcha\Providers\CloudflareTurnstileProvider;
use Illuminate\Support\ServiceProvider;

final class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CaptchaProviderInterface::class,
            CloudflareTurnstileProvider::class,
        );
    }
}
