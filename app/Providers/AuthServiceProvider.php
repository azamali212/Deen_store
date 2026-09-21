<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Policies\AuthPolicy;
use App\Domain\User\Policies\UserProfilePolicy;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [

        User::class => AuthPolicy::class,

        UserProfile::class => UserProfilePolicy::class,

    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user): ?bool {

            if ($user->hasRole('super_admin')) {
                return true;
            }

            return null;
        });
    }
}