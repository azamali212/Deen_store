<?php

use App\Domain\Audit\Exceptions\AiSummaryUnavailableException;
use App\Domain\Moderation\Exceptions\ModerationFlagAlreadyResolvedException;
use App\Domain\Moderation\Exceptions\ProfileContentRejectedException;
use App\Domain\User\Exceptions\AddressLimitExceededException;
use App\Domain\User\Exceptions\AddressNotFoundException;
use App\Domain\Auth\Exceptions\AccountLockedException;
use App\Domain\Auth\Exceptions\AuthException;
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

        $exceptions->render(
            function (AiSummaryUnavailableException $e) {
                $status = str_contains($e->getMessage(), 'no audit activity')
                    ? 404
                    : 503;

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $status);
            },
        );

        $exceptions->render(
            function (ModerationFlagAlreadyResolvedException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            },
        );

        $exceptions->render(
            function (ProfileContentRejectedException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'flagged_fields' => $e->context()['flagged_fields'] ?? [],
                ], 422);
            },
        );

        $exceptions->render(
            function (AddressLimitExceededException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            },
        );

        $exceptions->render(
            function (AddressNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            },
        );

        // Catch-all for every other Auth-domain exception (invalid
        // credentials, inactive account, panel access denied, invalid
        // social-login token, and more) — registered LAST so the two
        // more specific handlers above (which attach extra fields like
        // retry_after) still win for the exceptions they cover. Every
        // AuthException subclass already carries its own correct HTTP
        // status via statusCode() (set in its constructor) — this was
        // simply never wired up before now, so those exceptions were
        // falling through to a raw 500 instead of their real status.
        $exceptions->render(
            function (AuthException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->statusCode());
            },
        );
    })->create();
