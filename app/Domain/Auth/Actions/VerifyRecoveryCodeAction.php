<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\VerifyRecoveryCodeDTO;
use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Services\RecoveryCodeService;
use App\Models\TwoFactorRecoveryCode;
use RuntimeException;

final readonly class VerifyRecoveryCodeAction
{
    public function __construct(
        private RecoveryCodeService $service,
        private TwoFactorRepositoryInterface $repository,
    ) {}

    public function execute(
        VerifyRecoveryCodeDTO $dto,
    ): TwoFactorRecoveryCode {

        $user = $this->repository
            ->findUserById(
                $dto->userId,
            );

        if (! $user) {
            throw new RuntimeException(
                'User not found.',
            );
        }

        return $this->service
            ->verify(
                $user,
                $dto->code,
            );
    }
}
