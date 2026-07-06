<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Services\TrustedDeviceService;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListTrustedDevicesAction
{
    public function __construct(
        private TrustedDeviceService $service,
    ) {}

    public function execute(
        string $userId,
    ): Collection {

        return $this->service
            ->devices(
                $userId
            );
    }
}