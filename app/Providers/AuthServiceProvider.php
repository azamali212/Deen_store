<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Policies\AuthPolicy;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [

        User::class => AuthPolicy::class,

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