<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\ForgotPasswordDTO;
use App\Domain\Auth\DTO\ResetPasswordDTO;
use App\Domain\Auth\Events\PasswordResetCompleted;
use App\Domain\Auth\Events\PasswordResetRequested;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreatePasswordResetData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Domain\Auth\DTO\LoginRateLimitDTO;
use App\Domain\Auth\Enums\AuthPanel;

final readonly class PasswordResetService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
        private LoginRateLimitService $loginRateLimitService,
    ) {}

    public function request(
        ForgotPasswordDTO $dto
    ): void {

        $rateLimit = LoginRateLimitDTO::make(
            email: $dto->email,
            panel: AuthPanel::ADMIN,
            ipAddress: $dto->ipAddress,
        );

        $this->loginRateLimitService
            ->ensureIsNotLimited(
                $rateLimit
            );

        $user = $this->repository
            ->findUserByEmail(
                $dto->email
            );

        if (! $user instanceof User) {
            $this->loginRateLimitService
                ->hit(
                    $rateLimit
                );
            return;
        }

        if ($user->email_verified_at === null) {
            throw new \RuntimeException(
                'Please verify your email address first.'
            );
        }

        $this->repository
            ->deleteUserPasswordResets(
                (string) $user->id
            );

        $this->repository
            ->deleteExpiredPasswordResets();

        $token = Str::random(64);

        $this->repository
            ->createPasswordReset(
                new CreatePasswordResetData(
                    userId: (string) $user->id,
                    token: hash(
                        'sha256',
                        $token
                    ),
                    expiresAt: now()->addHour(),
                )
            );

        event(
            new PasswordResetRequested(
                user: $user,
                token: $token,
            )
        );

        $this->loginRateLimitService
            ->clear(
                $rateLimit
            );
    }

    public function reset(
        ResetPasswordDTO $dto
    ): void {

        DB::transaction(
            function () use ($dto): void {

                $passwordReset = $this->repository
                    ->findPasswordReset(
                        hash(
                            'sha256',
                            $dto->token
                        )
                    );

                if (! $passwordReset) {
                    throw new \RuntimeException(
                        'Invalid reset token.'
                    );
                }

                if ($passwordReset->used_at !== null) {
                    throw new \RuntimeException(
                        'Reset token has already been used.'
                    );
                }

                if ($passwordReset->expires_at->isPast()) {
                    throw new \RuntimeException(
                        'Reset token has expired.'
                    );
                }

                $user = $passwordReset->user;

                if (! $user instanceof User) {
                    throw new \RuntimeException(
                        'User not found.'
                    );
                }

                $this->repository
                    ->updateUser(
                        $user,
                        [
                            'password' => Hash::make(
                                $dto->password
                            ),
                        ]
                    );

                $this->repository
                    ->markPasswordResetUsed(
                        $passwordReset
                    );

                event(
                    new PasswordResetCompleted(
                        $user
                    )
                );
            }
        );
    }
}
