<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
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
    public function boot()
    {
        $paths = [
            database_path('migrations/auth'),
            database_path('migrations/vendor'),
            database_path('migrations/products'),
            database_path('migrations/orders'),
            database_path('migrations/wishlist'),
            database_path('migrations/cart'),
            database_path('migrations/shipment'),
        ];

        $this->loadMigrationsFrom($paths);
    }
}
