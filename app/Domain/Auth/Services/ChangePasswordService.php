<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Actions\EnsurePasswordNotReusedAction;
use App\Domain\Auth\Actions\StorePasswordHistoryAction;
use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Domain\Auth\Events\PasswordChanged;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final readonly class ChangePasswordService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
        private EnsurePasswordNotReusedAction $ensurePasswordNotReusedAction,
        private StorePasswordHistoryAction $storePasswordHistoryAction,
    ) {}

    public function change(
        ChangePasswordDTO $dto,
    ): void {

        DB::transaction(
            function () use ($dto): void {

                $user = $this->repository
                    ->findUserById(
                        $dto->userId,
                    );

                if (! $user) {
                    throw new RuntimeException(
                        'User not found.',
                    );
                }

                if (! Hash::check(
                    $dto->currentPassword,
                    $user->password,
                )) {
                    throw new RuntimeException(
                        'Current password is incorrect.',
                    );
                }

                if (Hash::check(
                    $dto->newPassword,
                    $user->password,
                )) {
                    throw new RuntimeException(
                        'New password must be different from current password.',
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Check Password History
                |--------------------------------------------------------------------------
                */

                $this->ensurePasswordNotReusedAction
                    ->execute(
                        user: $user,
                        newPassword: $dto->newPassword,
                    );

                /*
                |--------------------------------------------------------------------------
                | Store Current Password In History
                |--------------------------------------------------------------------------
                */

                $this->storePasswordHistoryAction
                    ->execute(
                        user: $user,
                        hashedPassword: $user->password,
                    );

                /*
                |--------------------------------------------------------------------------
                | Update Password
                |--------------------------------------------------------------------------
                */

                $this->repository
                    ->updateUser(
                        $user,
                        [
                            'password' => Hash::make(
                                $dto->newPassword,
                            ),
                        ],
                    );

                /*
                |--------------------------------------------------------------------------
                | Dispatch Event
                |--------------------------------------------------------------------------
                */

                event(
                    new PasswordChanged(
                        $user,
                    ),
                );
            },
        );
    }
}
