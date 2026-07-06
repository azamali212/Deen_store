<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTO\RevokeTrustedDeviceDTO;
use App\Domain\Auth\Services\TrustedDeviceService;

final readonly class RevokeTrustedDeviceAction
{
    public function __construct(
        private TrustedDeviceService $service,
    ) {}

    public function execute(
        RevokeTrustedDeviceDTO $dto,
    ): void {

        $this->service->revoke(
            $dto
        );
    }
}