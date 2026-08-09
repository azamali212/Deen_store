<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Audit\Repositories\AuditRepository;
use App\Domain\Audit\Repositories\Contracts\AuditRepositoryInterface;
use App\Domain\Audit\Services\AuditCorrelationService;
use Illuminate\Support\ServiceProvider;

final class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AuditRepositoryInterface::class,
            AuditRepository::class,
        );
        $this->app->singleton(
            AuditCorrelationService::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
