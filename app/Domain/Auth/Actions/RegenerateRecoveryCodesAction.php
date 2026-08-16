<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\RegenerateRecoveryCodesDTO;
use App\Domain\Auth\Repositories\Contracts\TwoFactorRepositoryInterface;
use App\Domain\Auth\Services\RecoveryCodeService;
use RuntimeException;

final readonly class RegenerateRecoveryCodesAction
{
    public function __construct(
        private RecoveryCodeService $service,
        private TwoFactorRepositoryInterface $repository,
    ) {}

    public function execute(
        RegenerateRecoveryCodesDTO $dto,
    ): array {

        $user = $this->repository
            ->findUserById(
                $dto->userId,
            );

        if (! $user) {
            throw new RuntimeException(
                'User not found.',
            );
        }

        return [
            'recovery_codes' => $this->service->regenerate(
                $user,
            ),
        ];
    }
}
