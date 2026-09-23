<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Contracts;

use App\Domain\Auth\Enums\UserAccountStatus;
use Illuminate\Pagination\LengthAwarePaginator;
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
use App\Models\UserSocialAccount;
use Illuminate\Database\Eloquent\Collection;
use App\Domain\Auth\Repositories\DTO\CreateEmailVerificationData;
use App\Domain\Auth\Repositories\DTO\CreatePasswordResetData;
use App\Models\PasswordReset;

use App\Models\EmailVerification;

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

    public function findValidOtp(int|string $userId, string $purpose): ?LoginOtp;

    public function invalidateOtps(int|string $userId, string $purpose): int;

    public function countActiveOtps(int|string $userId, string $purpose): int;

    public function createEmailVerification(CreateEmailVerificationData $data): EmailVerification;

    public function findEmailVerification(string $token): ?EmailVerification;

    public function markEmailVerified(EmailVerification $verification): bool;

    public function deleteExpiredEmailVerifications(): int;

    public function deleteUserEmailVerifications(int|string $userId): int;

    public function createPasswordReset(CreatePasswordResetData $data): PasswordReset;

    public function findPasswordReset(string $token): ?PasswordReset;

    public function deleteUserPasswordResets(int|string $userId): int;

    public function markPasswordResetUsed(PasswordReset $passwordReset): bool;

    public function deleteExpiredPasswordResets(): int;

    public function findSessionByToken(string $tokenId,): ?ActiveSession;
    
    public function terminateOtherSessions(int|string $userId,string $currentTokenId,): int;

    public function trustedDevices(int|string $userId,): Collection;
    
    public function revokeTrustedDevice(TrustedDevice $device,): bool;
    public function searchUsers(
        ?string $search,
        ?UserAccountStatus $status,
        ?string $role,
        ?bool $emailVerified,
        ?bool $phoneVerified,
        int $perPage,
    ): LengthAwarePaginator;

    public function emailExists(string $email, ?int $exceptUserId = null): bool;

    public function phoneExists(string $phone, ?int $exceptUserId = null): bool;

    public function deleteUser(User $user): bool;

    public function restoreUser(int|string $id): ?User;

    public function findSocialAccount(string $provider, string $providerUserId): ?UserSocialAccount;

    public function createSocialAccount(int|string $userId, string $provider, string $providerUserId, ?string $email): UserSocialAccount;
}
