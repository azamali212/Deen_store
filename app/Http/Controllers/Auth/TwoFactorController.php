<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Actions\ConfirmTwoFactorAction;
use App\Domain\Auth\Actions\DisableTwoFactorAction;
use App\Domain\Auth\Actions\EnableTwoFactorAction;
use App\Domain\Auth\Actions\RegenerateRecoveryCodesAction;
use App\Domain\Auth\Actions\VerifyRecoveryCodeAction;
use App\Domain\Auth\Actions\VerifyTwoFactorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Http\Requests\Auth\DisableTwoFactorRequest;
use App\Http\Requests\Auth\EnableTwoFactorRequest;
use App\Http\Requests\Auth\RegenerateRecoveryCodesRequest;
use App\Http\Requests\Auth\VerifyRecoveryCodeRequest;
use App\Http\Requests\Auth\VerifyTwoFactorRequest;
use App\Http\Resources\Auth\AuthResultResource;
use App\Http\Resources\Auth\RecoveryCodesResource;
use App\Http\Resources\Auth\TwoFactorResource;
use Illuminate\Http\JsonResponse;

final class TwoFactorController extends Controller
{
    public function __construct(
        private readonly EnableTwoFactorAction $enableAction,
        private readonly ConfirmTwoFactorAction $confirmAction,
        private readonly VerifyTwoFactorAction $verifyAction,
        private readonly DisableTwoFactorAction $disableAction,
        private readonly RegenerateRecoveryCodesAction $regenerateAction,
        private readonly VerifyRecoveryCodeAction $verifyRecoveryCodeAction,
    ) {}

    public function enable(
        EnableTwoFactorRequest $request,
    ): TwoFactorResource {

        return new TwoFactorResource(
            $this->enableAction->execute(
                $request->toDto(),
            ),
        );
    }

    public function confirm(
        ConfirmTwoFactorRequest $request,
    ): RecoveryCodesResource {

        return new RecoveryCodesResource(
            $this->confirmAction->execute(
                $request->toDto(),
            ),
        );
    }

    public function verify(
        VerifyTwoFactorRequest $request,
    ): AuthResultResource {
        return new AuthResultResource(
            $this->verifyAction->execute(
                $request->toDto(),
            ),
        );
    }

    public function disable(
        DisableTwoFactorRequest $request,
    ): JsonResponse {

        $this->disableAction->execute(
            $request->toDto(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled.',
        ]);
    }

    public function regenerateRecoveryCodes(
        RegenerateRecoveryCodesRequest $request,
    ): RecoveryCodesResource {

        return new RecoveryCodesResource(
            $this->regenerateAction->execute(
                $request->toDto(),
            ),
        );
    }

    public function verifyRecoveryCode(
        VerifyRecoveryCodeRequest $request,
    ): JsonResponse {

        $this->verifyRecoveryCodeAction->execute(
            $request->toDto(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Recovery code verified.',
        ]);
    }
}
