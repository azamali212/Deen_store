<?php

namespace App\Providers;

use App\Repositories\Auth\AuthRepository;
use App\Repositories\Auth\AuthRepositoryInterface;
use App\Repositories\CategoryManagement\ParentCategoryRepo\CategoryRepository;
use App\Repositories\CategoryManagement\ParentCategoryRepo\CategoryRepositoryInterface;
use App\Repositories\Email\EmailDraftsRepository;
use App\Repositories\Email\EmailDraftsRepositoryInterface;
use App\Repositories\Email\EmailRepository;
use App\Repositories\Email\EmailRepositoryInterface;
use App\Repositories\PermissionSettings\Permission\PermissionRepository;
use App\Repositories\PermissionSettings\Permission\PermissionRepositoryInterface;
use App\Repositories\PermissionSettings\Role\RoleRepository;
use App\Repositories\PermissionSettings\Role\RoleRepositoryInterface;
use App\Repositories\ProductManagement\AIFeatureProductRepo\AIProductRepository;
use App\Repositories\ProductManagement\AIFeatureProductRepo\AIProductRepositoryInterface;
use App\Repositories\ProductManagement\AIFeatureProductRepo\CartProductRepo\CartAbandonmentRepository;
use App\Repositories\ProductManagement\AIFeatureProductRepo\CartProductRepo\CartAbandonmentRepositoryInterface;
use App\Repositories\ProductManagement\AIFeatureProductRepo\CollaborativeFilteringRepo\CollaborativeFilteringRepository;
use App\Repositories\ProductManagement\AIFeatureProductRepo\CollaborativeFilteringRepo\CollaborativeFilteringRepositoryInterface;
use App\Repositories\ProductManagement\AIFeatureProductRepo\GeolocationRecommendationRepo\GeolocationRecommendationRepository;
use App\Repositories\ProductManagement\AIFeatureProductRepo\GeolocationRecommendationRepo\GeolocationRecommendationRepositoryInterface;
use App\Repositories\ProductManagement\AIFeatureProductRepo\UserActivityRepository;
use App\Repositories\ProductManagement\AIFeatureProductRepo\UserActivityRepositoryInterface;
use App\Repositories\ProductManagement\BaseRepo\BaseRepository;
use App\Repositories\ProductManagement\BaseRepo\BaseRepositoryInterface;
use App\Repositories\ProductManagement\ProductRepo\ProductRepository;
use App\Repositories\ProductManagement\ProductRepo\ProductRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\AI\Guzzle\AIAuth;
use App\Services\ProductManagementValidationService;
use Illuminate\Support\ServiceProvider;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);  // Keep only this line
        }
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
        $this->app->bind(AIProductRepositoryInterface::class,AIProductRepository::class);
        $this->app->bind(UserActivityRepositoryInterface::class,UserActivityRepository::class);
        $this->app->bind(CartAbandonmentRepositoryInterface::class,CartAbandonmentRepository::class);
        $this->app->bind(CollaborativeFilteringRepositoryInterface::class,CollaborativeFilteringRepository::class);
        $this->app->bind(GeolocationRecommendationRepositoryInterface::class,GeolocationRecommendationRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class,CategoryRepository::class);
        $this->app->bind(EmailDraftsRepositoryInterface::class,EmailDraftsRepository::class);


        
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
