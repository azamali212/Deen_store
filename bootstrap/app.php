<?php

use App\Domain\Audit\Exceptions\AiSummaryUnavailableException;
use App\Domain\Moderation\Exceptions\ModerationFlagAlreadyResolvedException;
use App\Domain\Moderation\Exceptions\ProfileContentRejectedException;
use App\Domain\User\Exceptions\AddressLimitExceededException;
use App\Domain\User\Exceptions\AddressNotFoundException;
use App\Domain\Seller\Exceptions\DuplicateStoreNameException;
use App\Domain\Seller\Exceptions\EmailNotVerifiedForSellingException;
use App\Domain\Seller\Exceptions\InvalidApplicationStatusTransitionException;
use App\Domain\Seller\Exceptions\MissingRequiredDocumentsException;
use App\Domain\Seller\Exceptions\SellerApplicationAlreadyExistsException;
use App\Domain\Seller\Exceptions\SellerApplicationNotFoundException;
use App\Domain\Seller\Exceptions\SellerDocumentNotFoundException;
use App\Domain\Seller\Exceptions\CannotReviewOwnApplicationException;
use App\Domain\Seller\Exceptions\SellerProfileNotFoundException;
use App\Domain\Seller\Exceptions\DocumentRejectedByAiException;
use App\Domain\Seller\Exceptions\DocumentVerificationUnavailableException;
use App\Domain\Seller\Exceptions\ApplicationFailedAiChecksException;
use App\Domain\Seller\Exceptions\SellerStoreSuspendedException;
use App\Domain\Seller\Exceptions\InvalidSellerStatusTransitionException;
use App\Domain\Seller\Exceptions\CannotManageOwnStoreException;
use App\Domain\Seller\Exceptions\InvalidBankVerificationStateException;
use App\Domain\Seller\Exceptions\SellerProfileNotFoundByAdminException;
use App\Domain\Seller\Exceptions\SellerTeamPermissionException;
use App\Domain\Seller\Exceptions\SellerTeamMemberNotFoundException;
use App\Domain\Seller\Exceptions\TeamInvitationNotAllowedException;
use App\Domain\Seller\Exceptions\TeamMembershipConflictException;
use App\Domain\Moderation\Exceptions\ModerationCheckFailedException;
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

        // ---- Seller domain (BLUEPRINT.txt D11) ----
        $exceptions->render(
            function (SellerApplicationAlreadyExistsException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            },
        );

        $exceptions->render(
            function (SellerApplicationNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            },
        );

        $exceptions->render(
            function (InvalidApplicationStatusTransitionException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'current_status' => $e->context()['current_status'] ?? null,
                ], 409);
            },
        );

        // Same shape as a normal validation error, so the frontend shows it
        // under the store_name field like any other field error.
        $exceptions->render(
            function (DuplicateStoreNameException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [
                        'store_name' => [$e->getMessage()],
                    ],
                ], 422);
            },
        );

        $exceptions->render(
            function (MissingRequiredDocumentsException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'missing_documents' => $e->context()['missing_documents'] ?? [],
                ], 422);
            },
        );

        $exceptions->render(
            function (EmailNotVerifiedForSellingException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            },
        );

        $exceptions->render(
            function (SellerDocumentNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            },
        );

        $exceptions->render(
            function (CannotReviewOwnApplicationException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            },
        );

        $exceptions->render(
            function (SellerProfileNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            },
        );

        // Pre-existing gap (since moderation became blocking): when Gemini is
        // down or not configured, profile/avatar/store updates used to crash
        // with a raw 500. Now a clear 503. The real reason (e.g. missing
        // GEMINI_API_KEY) is only logged — never shown to the user.
        $exceptions->render(
            function (ModerationCheckFailedException $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Content check is temporarily unavailable. Please try again in a few minutes.',
                ], 503);
            },
        );

        // ---- Seller AI verification (BLUEPRINT section 10) ----
        $exceptions->render(
            function (DocumentRejectedByAiException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'document_type' => $e->context()['document_type'] ?? null,
                    'reason' => $e->context()['reason'] ?? null,
                ], 422);
            },
        );

        $exceptions->render(
            function (ApplicationFailedAiChecksException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'failed_checks' => $e->context()['failed_checks'] ?? [],
                ], 422);
            },
        );

        $exceptions->render(
            function (DocumentVerificationUnavailableException $e) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => 'Document verification is temporarily unavailable. Please try again in a few minutes.',
                ], 503);
            },
        );

        // ---- Phase 6: live-store control (BLUEPRINT section 11) ----
        $exceptions->render(
            function (SellerProfileNotFoundByAdminException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            },
        );

        $exceptions->render(
            function (SellerStoreSuspendedException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            },
        );

        $exceptions->render(
            function (CannotManageOwnStoreException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            },
        );

        $exceptions->render(
            function (InvalidSellerStatusTransitionException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'current_status' => $e->context()['current_status'] ?? null,
                ], 409);
            },
        );

        $exceptions->render(
            function (InvalidBankVerificationStateException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            },
        );

        // ---- Phase 7: seller team (BLUEPRINT section 12) ----
        $exceptions->render(
            function (SellerTeamPermissionException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 403);
            },
        );

        $exceptions->render(
            function (SellerTeamMemberNotFoundException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 404);
            },
        );

        // Shaped like a validation error so the frontend can show it under
        // the email field.
        $exceptions->render(
            function (TeamInvitationNotAllowedException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => [
                        'email' => [$e->getMessage()],
                    ],
                ], 422);
            },
        );

        $exceptions->render(
            function (TeamMembershipConflictException $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
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
