<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

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
    ) {}

    public function change(
        ChangePasswordDTO $dto
    ): void {

        DB::transaction(
            function () use ($dto): void {

                $user = $this->repository
                    ->findUserById(
                        $dto->userId
                    );

                if (! $user) {
                    throw new RuntimeException(
                        'User not found.'
                    );
                }

                if (! Hash::check(
                    $dto->currentPassword,
                    $user->password
                )) {
                    throw new RuntimeException(
                        'Current password is incorrect.'
                    );
                }

                if (Hash::check(
                    $dto->newPassword,
                    $user->password
                )) {
                    throw new RuntimeException(
                        'New password must be different from current password.'
                    );
                }

                $this->repository
                    ->updateUser(
                        $user,
                        [
                            'password' => Hash::make(
                                $dto->newPassword
                            ),
                        ]
                    );

                event(
                    new PasswordChanged(
                        $user
                    )
                );
            }
        );
    }
}