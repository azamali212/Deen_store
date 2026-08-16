<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use App\Domain\Auth\DTO\AuthResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuthResult
 */
final class AuthResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,

            'user' => new UserResource(
                $this->user,
            ),

            'token' => $this->token,

            'token_name' => $this->tokenName,

            'session_id' => $this->sessionId,

            'abilities' => $this->abilities,

            'accessible_panels' => $this->accessiblePanels,

            'requires_otp' => $this->requiresOtp,

            'requires_step_up' => $this->requiresStepUp,

            'message' => $this->message,
            'requires_two_factor' => $this->requiresTwoFactor,
        ];
    }
}
