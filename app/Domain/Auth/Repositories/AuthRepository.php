<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories;

use App\Domain\Auth\Enums\OtpPurpose;
use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateLoginLogData;
use App\Domain\Auth\Repositories\DTO\CreateOtpData;
use App\Domain\Auth\Repositories\DTO\CreateSessionData;
use App\Domain\Auth\Repositories\DTO\CreateTrustedDeviceData;
use App\Domain\Auth\Repositories\DTO\CreateUserData;
use App\Domain\Auth\Repositories\Queries\OtpQuery;
use App\Domain\Auth\Repositories\Queries\SessionQuery;
use App\Domain\Auth\Repositories\Queries\TrustedDeviceQuery;
use App\Domain\Auth\Repositories\Queries\UserQuery;
use App\Models\ActiveSession;
use App\Models\LoginLog;
use App\Models\LoginOtp;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(
        private UserQuery $users,
        private OtpQuery $otps,
        private SessionQuery $sessions,
        private TrustedDeviceQuery $trustedDevices,
    ) {}

    public function findUserByEmail(string $email): ?User
    {
        return $this->users->byEmail($email)->first();
    }

    public function findUserById(int|string $id): ?User
    {
        return $this->users->byId($id)->first();
    }

    public function createUser(CreateUserData $data): User
    {
        return User::query()->create($data->toArray());
    }

    public function updateUser(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function createOtp(CreateOtpData $data): LoginOtp
    {
        return LoginOtp::query()->create($data->toArray());
    }

    public function latestOtp(string $identifier, mixed $purpose): ?LoginOtp
    {
        $purpose = $purpose instanceof OtpPurpose
            ? $purpose
            : OtpPurpose::from((string) $purpose);

        return $this->otps
            ->latestForIdentifier($identifier, $purpose)
            ->first();
    }

    public function deleteOtp(LoginOtp $otp): bool
    {
        return (bool) $otp->delete();
    }

    public function createSession(CreateSessionData $data): ActiveSession
    {
        return ActiveSession::query()->create($data->toArray());
    }

    public function terminateSession(string $tokenId): int
    {
        return $this->sessions
            ->byTokenId($tokenId)
            ->whereNull('terminated_at')
            ->update([
                'terminated_at' => now(),
            ]);
    }

    public function terminateAllSessions(int|string $userId): int
    {
        return $this->sessions
            ->activeForUser($userId)
            ->update([
                'terminated_at' => now(),
            ]);
    }

    public function createLoginLog(CreateLoginLogData $data): LoginLog
    {
        return LoginLog::query()->create($data->toArray());
    }

    public function saveTrustedDevice(CreateTrustedDeviceData $data): TrustedDevice
    {
        return TrustedDevice::query()->updateOrCreate(
            [
                'user_id' => $data->userId,
                'fingerprint' => $data->fingerprint,
            ],
            $data->toArray()
        );
    }

    public function findTrustedDevice(int|string $userId, string $fingerprint): ?TrustedDevice
    {
        return $this->trustedDevices
            ->trusted($userId, $fingerprint)
            ->first();
    }

    public function activeSessions(int|string $userId): Collection
    {
        return $this->sessions
            ->activeForUser($userId)
            ->get();
    }

    public function findValidOtp(
        int|string $userId,
        string $code,
        string $purpose
    ): ?LoginOtp
    {
        return LoginOtp::query()
            ->where('user_id', $userId)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function invalidateOtps(
        int|string $userId,
        string $purpose
    ): int
    {
        return LoginOtp::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->update([
                'expires_at' => now(),
            ]);
    }

    public function countActiveOtps(
        int|string $userId,
        string $purpose
    ): int
    {
        return LoginOtp::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->count();
    }
}