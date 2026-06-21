<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Services\AuthService;
use App\Models\User;

final readonly class VerifyOtpAction
{
    public function __construct(
        private AuthService $authService,
    ) {}

    public function execute(
        User $user,
        string $code,
        OtpPurpose $purpose
    ): bool {
        return $this->authService->verifyOtp(
            $user,
            $code,
            $purpose
        );
    }
}