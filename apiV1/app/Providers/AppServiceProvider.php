<?php

namespace App\Providers;

use App\Repositories\Auth\AuthRepository;
use App\Repositories\Auth\AuthRepositoryInterface;
use App\Repositories\Email\EmailRepository;
use App\Repositories\Email\EmailRepositoryInterface;
use App\Repositories\PermissionSettings\Permission\PermissionRepository;
use App\Repositories\PermissionSettings\Permission\PermissionRepositoryInterface;
use App\Repositories\PermissionSettings\Role\RoleRepository;
use App\Repositories\PermissionSettings\Role\RoleRepositoryInterface;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;
use App\Repositories\ProductManagement\BaseRepo\BaseRepositoryInterface;
use App\Repositories\ProductManagement\ProductRepo\ProductRepository;
use App\Repositories\ProductManagement\ProductRepo\ProductRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\AI\Guzzle\AIAuth;
use App\Services\ProductManagementValidationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);
        $this->app->bind(UserRepositoryInterface::class,UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class,RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class,concrete: PermissionRepository::class);


        //Product Repository
        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(ProductRepositoryInterface::class,ProductRepository::class);

        // Register all other repositories if needed
        $this->app->bind(\App\Repositories\ProductManagement\CategoryRepository::class, \App\Repositories\ProductManagement\CategoryRepository::class);
        $this->app->bind(\App\Repositories\ProductManagement\SubCategoryRepository::class, \App\Repositories\ProductManagement\SubCategoryRepository::class);
        $this->app->bind(\App\Repositories\ProductManagement\BrandRepository::class, \App\Repositories\ProductManagement\BrandRepository::class);
        $this->app->bind(\App\Repositories\ProductManagement\VariantRepository::class, \App\Repositories\ProductManagement\VariantRepository::class);
        $this->app->bind(\App\Repositories\ProductManagement\ProductImageRepository::class, \App\Repositories\ProductManagement\ProductImageRepository::class);
        $this->app->bind(\App\Repositories\ProductManagement\TagRepository::class, \App\Repositories\ProductManagement\TagRepository::class);

        //Validations Services
        $this->app->singleton(ProductManagementValidationService::class, function ($app) {
            return new ProductManagementValidationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
