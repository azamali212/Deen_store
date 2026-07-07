<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\LoginRateLimitDTO;
use App\Domain\Auth\Exceptions\TooManyLoginAttemptsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final readonly class LoginRateLimitService
{
    public function ensureIsNotLimited(
        LoginRateLimitDTO $dto,
    ): void {

        if (! RateLimiter::tooManyAttempts(
            $this->key($dto),
            $this->maxAttempts()
        )) {
            return;
        }

        throw new TooManyLoginAttemptsException(
            RateLimiter::availableIn(
                $this->key($dto)
            )
        );
    }

    public function hit(
        LoginRateLimitDTO $dto,
    ): void {

        RateLimiter::hit(
            $this->key($dto),
            $this->decaySeconds()
        );
    }

    public function clear(
        LoginRateLimitDTO $dto,
    ): void {

        RateLimiter::clear(
            $this->key($dto)
        );
    }
    private function key(
        LoginRateLimitDTO $dto,
    ): string {

        return 'login:'
            .$dto->panel->value
            .':'
            .Str::lower($dto->email)
            .':'
            .($dto->ipAddress ?? 'unknown-ip');
    }
    private function maxAttempts(): int
    {
        return (int) config(
            'auth_security.login.max_attempts',
            5
        );
    }

    private function decaySeconds(): int
    {
        return (int) config(
            'auth_security.login.decay_seconds',
            900
        );
    }
}