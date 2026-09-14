<?php

namespace App\Providers;

use App\Domain\Auth\Repositories\AuthRepository;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\Contracts\PasswordHistoryRepositoryInterface;
use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Repositories\PasswordHistoryRepository;
use App\Domain\Auth\Repositories\TwoFactorRepository;
use App\Domain\User\Repositories\Contracts\UserAddressRepositoryInterface;
use App\Domain\User\Repositories\Contracts\UserPreferenceRepositoryInterface;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;
use App\Domain\User\Repositories\UserAddressRepository;
use App\Domain\User\Repositories\UserPreferenceRepository;
use App\Domain\User\Repositories\UserProfileRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Auth domain
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(PasswordHistoryRepositoryInterface::class, PasswordHistoryRepository::class);
        $this->app->bind(TwoFactorRepositoryInterface::class, TwoFactorRepository::class);

        // User domain
        $this->app->bind(UserProfileRepositoryInterface::class, UserProfileRepository::class);
        $this->app->bind(UserAddressRepositoryInterface::class, UserAddressRepository::class);
        $this->app->bind(UserPreferenceRepositoryInterface::class, UserPreferenceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
