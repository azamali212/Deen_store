<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTO;

use App\Models\User;

final readonly class AuthResult
{
    public function __construct(
        public User $user,
        public string $token,
        public string $tokenName,
        public ?string $sessionId,
        public array $abilities,
        public array $accessiblePanels,
        public bool $requiresOtp,
        public bool $requiresStepUp,
        public ?string $message,
        public bool $requiresTwoFactor,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'token' => $this->token,
            'token_name' => $this->tokenName,
            'session_id' => $this->sessionId,
            'abilities' => $this->abilities,
            'accessible_panels' => $this->accessiblePanels,
            'requires_otp' => $this->requiresOtp,
            'requires_step_up' => $this->requiresStepUp,
            'message' => $this->message,
        ];
    }
}
