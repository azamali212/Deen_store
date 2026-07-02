<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\ChangePasswordDTO;
use App\Domain\Auth\Services\ChangePasswordService;

final readonly class ChangePasswordAction
{
    public function __construct(
        private ChangePasswordService $service,
    ) {}

    public function execute(
        ChangePasswordDTO $dto,
    ): void {

        $this->service->change(
            $dto
        );
    }
}