<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Contracts;

use App\Domain\Auth\Repositories\DTO\CreateLoginLogData;
use App\Domain\Auth\Repositories\DTO\CreateOtpData;
use App\Domain\Auth\Repositories\DTO\CreateSessionData;
use App\Domain\Auth\Repositories\DTO\CreateTrustedDeviceData;
use App\Domain\Auth\Repositories\DTO\CreateUserData;
use App\Models\ActiveSession;
use App\Models\LoginLog;
use App\Models\LoginOtp;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User;

    public function findUserById(int|string $id): ?User;

    public function createUser(CreateUserData $data): User;

    public function updateUser(User $user, array $data): bool;

    public function createOtp(CreateOtpData $data): LoginOtp;

    public function latestOtp(string $identifier, mixed $purpose): ?LoginOtp;

    public function deleteOtp(LoginOtp $otp): bool;

    public function createSession(CreateSessionData $data): ActiveSession;

    public function terminateSession(string $tokenId): int;

    public function terminateAllSessions(int|string $userId): int;

    public function createLoginLog(CreateLoginLogData $data): LoginLog;

    public function saveTrustedDevice(CreateTrustedDeviceData $data): TrustedDevice;

    public function findTrustedDevice(int|string $userId, string $fingerprint): ?TrustedDevice;

    public function activeSessions(int|string $userId): Collection;
}