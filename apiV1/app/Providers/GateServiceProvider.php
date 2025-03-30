<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class GateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('inventory-manage', function ($user) {
            return $user->hasPermissionTo('inventory-index') &&
                   $user->hasPermissionTo('inventory-create') &&
                   $user->hasPermissionTo('inventory-edit') &&
                   $user->hasPermissionTo('inventory-delete') &&
                   $user->hasPermissionTo('inventory-log-view') &&
                   $user->hasPermissionTo('inventory-log-create') &&
                   $user->hasPermissionTo('inventory-forecast-sales') &&
                   $user->hasPermissionTo('inventory-auto-restock') &&
                   $user->hasPermissionTo('inventory-track-batch-expiry') &&
                   $user->hasPermissionTo('inventory-report-generate') &&
                   $user->hasPermissionTo('inventory-report-export') &&
                   $user->hasPermissionTo('inventory-transfer') &&
                   $user->hasPermissionTo('inventory-stock-level') &&
                   $user->hasPermissionTo('inventory-allocate-stock-order') &&
                   $user->hasPermissionTo('inventory-warehouse-stock');
        });
    }
}
