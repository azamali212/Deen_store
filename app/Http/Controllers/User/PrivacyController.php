<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Enums\ConsentType;
use App\Domain\User\Services\AccountErasureService;
use App\Domain\User\Services\ConsentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\GrantConsentRequest;
use App\Http\Requests\User\RequestAccountErasureRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * A5 — everything a user can do about their own data, in one place:
 * export it (GET privacy/data-export, already built), see and change what
 * they have agreed to, and ask to be erased.
 *
 * Every route resolves off $request->user(). There is no {user} parameter
 * anywhere in this controller, so there is no way to aim any of it at
 * somebody else's account.
 */
final class PrivacyController extends Controller
{
    // ------------------------------------------------------------- erasure

    /**
     * What the privacy screen needs to render itself: is a deletion pending,
     * when would it run, and if it is not possible yet, why not.
     */
    public function erasureStatus(
        Request $request,
        AccountErasureService $erasure,
    ): JsonResponse {

        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => $user->hasPendingErasure(),
                'requested_at' => $user->erasure_requested_at?->toIso8601String(),
                'scheduled_for' => $erasure->scheduledFor($user)?->toIso8601String(),
                'grace_days' => (int) config('privacy.erasure_grace_days'),
                'blocked_by' => $erasure->blockingReasons($user),
                'confirmation_phrase' => RequestAccountErasureRequest::CONFIRMATION_PHRASE,
                // Art. 15 before Art. 17: offer the copy before the deletion,
                // because after it there is nothing left to give them.
                'data_export_url' => route('privacy.data-export'),
            ],
        ]);
    }

    public function requestErasure(
        RequestAccountErasureRequest $request,
        AccountErasureService $erasure,
    ): JsonResponse {

        $user = $erasure->request($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Your account is scheduled for deletion. You can cancel until then.',
            'data' => [
                'requested_at' => $user->erasure_requested_at?->toIso8601String(),
                'scheduled_for' => $erasure->scheduledFor($user)?->toIso8601String(),
            ],
        ], 202);
    }

    public function cancelErasure(
        Request $request,
        AccountErasureService $erasure,
    ): JsonResponse {

        $erasure->cancel($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Your deletion request has been cancelled. Nothing was removed.',
        ]);
    }

    // ------------------------------------------------------------- consent

    public function consents(
        Request $request,
        ConsentService $consents,
    ): JsonResponse {

        return response()->json([
            'success' => true,
            'data' => $consents->current($request->user()),
        ]);
    }

    public function grantConsent(
        GrantConsentRequest $request,
        ConsentService $consents,
    ): JsonResponse {

        $consent = $consents->grant($request->user(), $request->consentType());

        return response()->json([
            'success' => true,
            'message' => 'Recorded.',
            'data' => [
                'type' => $consent->type->value,
                'version' => $consent->version,
                'granted_at' => $consent->granted_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function withdrawConsent(
        Request $request,
        ConsentService $consents,
        string $type,
    ): JsonResponse {

        // Route parameter, so an unknown value is a 422 rather than an
        // uncaught ValueError out of ConsentType::from().
        $consentType = ConsentType::tryFrom($type);

        if ($consentType === null) {
            throw ValidationException::withMessages([
                'type' => ['Unknown consent type: '.$type],
            ]);
        }

        $consents->withdraw($request->user(), $consentType);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawn.',
            'data' => $consents->current($request->user()),
        ]);
    }
}
