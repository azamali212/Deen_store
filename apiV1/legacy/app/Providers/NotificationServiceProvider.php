<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Notifications\Channels\BroadcastChannel;
use App\Domain\Notifications\Channels\ChannelManager;
use App\Domain\Notifications\Channels\DatabaseChannel;
use App\Domain\Notifications\Enums\NotificationChannel;
use App\Domain\Notifications\Repositories\NotificationRepository;
use App\Domain\Notifications\Repositories\PreferenceRepository;
use App\Domain\Notifications\Repositories\TemplateRepository;
use App\Domain\Notifications\Services\NotificationService;
use App\Domain\Notifications\Support\TemplateRenderer;
use Illuminate\Support\ServiceProvider;
use App\Domain\Notifications\Channels\MailChannel;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationRepository::class);
        $this->app->singleton(PreferenceRepository::class);
        $this->app->singleton(MailChannel::class);
        $this->app->singleton(TemplateRepository::class);
        $this->app->singleton(TemplateRenderer::class);

        $this->app->singleton(DatabaseChannel::class);
        $this->app->singleton(BroadcastChannel::class);

        $this->app->singleton(ChannelManager::class, function ($app): ChannelManager {
            return new ChannelManager([
                NotificationChannel::DATABASE->value => $app->make(DatabaseChannel::class),
                NotificationChannel::BROADCAST->value => $app->make(BroadcastChannel::class),
                NotificationChannel::MAIL->value => $app->make(MailChannel::class),
            ]);
        });

        $this->app->singleton(NotificationService::class);
    }
}