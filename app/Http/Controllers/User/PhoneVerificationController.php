<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Domain\User\Actions\RequestPhoneVerificationAction;
use App\Domain\User\Actions\VerifyPhoneAction;
use App\Domain\User\DTO\VerifyPhoneDTO;
use App\Domain\User\ValueObjects\PhoneNumber;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\RequestPhoneVerificationRequest;
use App\Http\Requests\User\VerifyPhoneRequest;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;

final class PhoneVerificationController extends Controller
{
    // Send a 6-digit code for the phone number the user wants to verify
    // (logged for now, not actually SMS'd — no provider wired up yet).
    public function sendCode(
        RequestPhoneVerificationRequest $request,
        RequestPhoneVerificationAction $action,
    ): JsonResponse {

        $action->execute(
            $request->user(),
            PhoneNumber::from($request->validated('phone')),
        );

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent.',
        ]);
    }

    // Confirm the code and mark the phone number as verified
    public function verify(
        VerifyPhoneRequest $request,
        VerifyPhoneAction $action,
    ): JsonResponse {

        $dto = VerifyPhoneDTO::fromArray(
            $request->validated(),
            $request->user()->id,
        );

        $user = $action->execute(
            $request->user(),
            $dto,
        );

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
            'data' => new UserResource($user),
        ]);
    }
}
