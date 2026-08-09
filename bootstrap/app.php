<?php

use App\Domain\Auth\Exceptions\AccountLockedException;
use App\Domain\Auth\Exceptions\TooManyLoginAttemptsException;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureAuditCorrelationId;
use App\Http\Middleware\EnsureOtpVerified;
use App\Http\Middleware\EnsurePanelAccess;
use App\Http\Middleware\EnsureTrustedDevice;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(
            append: [
                EnsureAuditCorrelationId::class,
            ],
        );
        $middleware->alias([
            'panel' => EnsurePanelAccess::class,
            'active' => EnsureAccountIsActive::class,
            'otp' => EnsureOtpVerified::class,
            'trusted' => EnsureTrustedDevice::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // EnsureAuditCorrelationId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        $exceptions->render(
            function (
                TooManyLoginAttemptsException $e,
                // Request $request,
            ) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'retry_after' => $e->secondsUntilAvailable,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            },
        );

        $exceptions->render(
            function (AccountLockedException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'retry_after' => $e->retryAfter,
                    'locked_until' => $e->lockedUntil,
                ], 423);
            },
        );
    })->create();
