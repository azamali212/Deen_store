<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Services\RecoveryCodeService;
use App\Models\User;

final readonly class GenerateRecoveryCodesAction
{
    public function __construct(
        private RecoveryCodeService $service,
    ) {}

    public function execute(
        User $user,
    ): array {

        return $this->service
            ->generate(
                $user,
            );
    }
}