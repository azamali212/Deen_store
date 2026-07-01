<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\ResendVerificationDTO;
use App\Domain\Auth\DTO\VerifyEmailDTO;
use App\Domain\Auth\Events\EmailVerified;
use App\Domain\Auth\Events\EmailVerificationRequested;
use App\Domain\Auth\Repositories\DTO\CreateEmailVerificationData;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class EmailVerificationService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
    ) {}

    public function request(
        User $user
    ): void {
        $token = Str::random(64);
        $this->repository->createEmailVerification(
            new CreateEmailVerificationData(
                userId: (string) $user->id,
                token: hash('sha256', $token),
                expiresAt: now()->addHour(),
            )
        );
        event(
            new EmailVerificationRequested(
                user: $user,
                token: $token,
            )
        );
    }

    public function verify(
        VerifyEmailDTO $dto
    ): User {

        return DB::transaction(
            function () use ($dto): User {

                $verification = $this->repository
                    ->findEmailVerification(
                        hash('sha256', $dto->token)
                    );

                if (! $verification) {
                    throw new \RuntimeException(
                        'Invalid verification token.'
                    );
                }

                if ($verification->verified_at !== null) {
                    throw new \RuntimeException(
                        'Email address has already been verified.'
                    );
                }

                if ($verification->expires_at->isPast()) {
                    throw new \RuntimeException(
                        'Verification link has expired.'
                    );
                }

                $user = $verification->user;

                if (! $user instanceof User) {
                    throw new \RuntimeException(
                        'User not found.'
                    );
                }

                $this->repository->updateUser(
                    $user,
                    [
                        'email_verified_at' => now(),
                    ]
                    
                );
                $this->repository->markEmailVerified(
                    $verification
                );

                event(
                    new EmailVerified(
                        $user
                    )
                );

                return $user->fresh();
            }
        );
    }
    public function resend(ResendVerificationDTO $dto): void 
    {
        //dd('Resend Method Called');
        $user = $this->repository
            ->findUserByEmail(
                $dto->email
            );
    
        if (! $user instanceof User) {
            throw new \RuntimeException(
                'User not found.'
            );
        }
    
        if ($user->email_verified_at !== null) {
            throw new \RuntimeException(
                'Email address is already verified.'
            );
        }
    
        $this->repository
            ->deleteUserEmailVerifications(
                (string) $user->id
            );
    
        $token = Str::random(64);
    
        $this->repository
            ->createEmailVerification(
                new CreateEmailVerificationData(
                    userId: (string) $user->id,
                    token: hash(
                        'sha256',
                        $token
                    ),
                    expiresAt: now()->addHour(),
                )
            );
            //dd($token);
        event(
            new EmailVerificationRequested(
                user: $user,
                token: $token,
            )
        );
    }
}
