<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTO\RevokeTrustedDeviceDTO;
use App\Domain\Auth\Events\TrustedDeviceRevoked;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

final readonly class TrustedDeviceService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
    ) {}

    public function devices(
        string $userId,
    ): Collection {

        return $this->repository
            ->trustedDevices(
                $userId
            );
    }

    public function revoke(
        RevokeTrustedDeviceDTO $dto,
    ): void {

        $device = $this->repository
            ->findTrustedDevice(
                $dto->userId,
                $dto->fingerprint
            );

        if (! $device) {
            throw new RuntimeException(
                'Trusted device not found.'
            );
        }

        $this->repository
            ->revokeTrustedDevice(
                $device
            );

        event(
            new TrustedDeviceRevoked(
                $device
            )
        );
    }
}
