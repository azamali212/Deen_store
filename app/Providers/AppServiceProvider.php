<?php

namespace App\Providers;

use App\Domain\Auth\Repositories\AuthRepository;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\Contracts\PasswordHistoryRepositoryInterface;
use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Repositories\PasswordHistoryRepository;
use App\Domain\Auth\Repositories\TwoFactorRepository;
use App\Domain\Auth\Contracts\GoogleTokenVerifierInterface;
use App\Domain\Auth\Support\GoogleIdTokenVerifier;
use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\Contracts\LogoStorageInterface;
use App\Domain\Seller\Repositories\Contracts\SellerApplicationRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerRenewalRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerTeamRepositoryInterface;
use App\Domain\Seller\Repositories\SellerApplicationRepository;
use App\Domain\Seller\Repositories\SellerProfileRepository;
use App\Domain\Seller\Repositories\SellerRenewalRepository;
use App\Domain\Seller\Repositories\SellerTeamRepository;
use App\Domain\Seller\Support\GeminiDocumentVerifier;
use App\Domain\Seller\Support\LocalDocumentStorage;
use App\Domain\Seller\Support\NullDocumentVerifier;
use App\Domain\Seller\Support\LocalLogoStorage;
use App\Domain\User\Contracts\AvatarStorageInterface;
use App\Domain\User\Contracts\SmsGatewayInterface;
use App\Domain\User\Repositories\Contracts\UserAddressRepositoryInterface;
use App\Domain\User\Repositories\Contracts\UserPreferenceRepositoryInterface;
use App\Domain\User\Repositories\Contracts\UserProfileRepositoryInterface;
use App\Domain\User\Repositories\UserAddressRepository;
use App\Domain\User\Repositories\UserPreferenceRepository;
use App\Domain\User\Repositories\UserProfileRepository;
use App\Domain\User\Support\LocalAvatarStorage;
use App\Domain\User\Support\LogSmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->bind(AvatarStorageInterface::class, LocalAvatarStorage::class);
        $this->app->bind(SmsGatewayInterface::class, LogSmsGateway::class);
        $this->app->bind(GoogleTokenVerifierInterface::class, GoogleIdTokenVerifier::class);

        // Seller domain
        $this->app->bind(SellerApplicationRepositoryInterface::class, SellerApplicationRepository::class);
        $this->app->bind(SellerProfileRepositoryInterface::class, SellerProfileRepository::class);
        $this->app->bind(SellerTeamRepositoryInterface::class, SellerTeamRepository::class);
        $this->app->bind(SellerRenewalRepositoryInterface::class, SellerRenewalRepository::class);
        $this->app->bind(DocumentStorageInterface::class, LocalDocumentStorage::class);
        $this->app->bind(LogoStorageInterface::class, LocalLogoStorage::class);

        // KYC documents go to Gemini ONLY when explicitly switched on —
        // and it must only be switched on with the PAID tier (billing).
        // Default: NullDocumentVerifier, nothing leaves the server.
        $this->app->bind(DocumentVerifierInterface::class, function ($app): DocumentVerifierInterface {
            return config('services.gemini.document_verification.enabled')
                ? $app->make(GeminiDocumentVerifier::class)
                : new NullDocumentVerifier();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRateLimiters();
    }

    /**
     * C35 — every seller document upload is a BILLABLE AI call, made
     * BEFORE the file is stored, so even a rejected upload costs money.
     * These limits sit on the routes, ahead of the controllers, so a
     * throttled request never reaches the AI at all.
     *
     * Keyed per authenticated USER, not per IP: everyone here is logged
     * in, and an IP key would punish a whole office behind one connection.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('seller-documents', fn (Request $request): Limit => Limit::perHour(20)
            ->by((string) ($request->user()?->id ?? $request->ip())));

        RateLimiter::for('seller-bank-proof', fn (Request $request): Limit => Limit::perHour(5)
            ->by((string) ($request->user()?->id ?? $request->ip())));
    }
}
