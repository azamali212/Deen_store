<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Events\RecoveryCodeUsed;
use App\Domain\Auth\Exceptions\InvalidRecoveryCodeException;
use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateRecoveryCodeData;
use App\Domain\Auth\Support\RecoveryCodeGenerator;
use App\Models\TwoFactorRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final readonly class RecoveryCodeService
{
    public function __construct(
        private TwoFactorRepositoryInterface $repository,
        private RecoveryCodeGenerator $generator,
    ) {}

    public function generate(
        User $user,
    ): array {

        return DB::transaction(
            function () use ($user): array {

                $this->repository
                    ->deleteRecoveryCodes(
                        $user,
                    );

                $plainCodes = $this->generator
                    ->generate();

                foreach ($plainCodes as $code) {

                    $this->repository
                        ->createRecoveryCode(
                            new CreateRecoveryCodeData(
                                uuid: (string) Str::uuid(),
                                userId: (string) $user->id,
                                code: Hash::make(
                                    $code,
                                ),
                            ),
                        );
                }

                return $plainCodes;
            },
        );
    }

    public function verify(
        User $user,
        string $code,
    ): TwoFactorRecoveryCode {

        $histories = $this->repository
            ->recoveryCodes(
                $user,
            );

        foreach ($histories as $history) {

            if ($history->used_at !== null) {
                continue;
            }

            if (! Hash::check(
                $code,
                $history->code,
            )) {
                continue;
            }

            $this->repository
                ->markRecoveryCodeAsUsed(
                    $history,
                );

            event(
                new RecoveryCodeUsed(
                    user: $user,
                    recoveryCode: $history,
                ),
            );

            return $history;
        }

        throw new InvalidRecoveryCodeException;
    }

    public function regenerate(
        User $user,
    ): array {

        $this->repository
            ->deleteRecoveryCodes(
                $user,
            );

        return $this->generate(
            $user,
        );
    }
}