<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreateSessionData;
use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Domain\Auth\DTO\LogoutOtherSessionsDTO;
use App\Domain\Auth\DTO\LogoutSessionDTO;
use App\Domain\Auth\Events\SessionTerminated;

use RuntimeException;

final readonly class SessionService
{
    public function __construct(
        private AuthRepositoryInterface $repository,
    ) {}

    public function create(
        CreateSessionData $data
    ): ActiveSession {
        return $this->repository->createSession($data);
    }

    public function terminate(
        string $tokenId
    ): int {
        return $this->repository->terminateSession($tokenId);
    }

    public function terminateAllForUser(
        User $user
    ): int {
        return $this->repository->terminateAllSessions(
            $user->id
        );
    }

    public function activeSessions(
        User $user
    ): Collection {
        return $this->repository->activeSessions(
            $user->id
        );
    }

    public function updateActivity(
        ActiveSession $session
    ): bool {
        return $session->update([
            'last_activity_at' => now(),
        ]);
    }

    public function markTerminated(
        ActiveSession $session
    ): bool {
        return $session->update([
            'terminated_at' => now(),
        ]);
    }

    public function hasActiveSessions(
        User $user
    ): bool {
        return $this->activeSessions($user)
            ->isNotEmpty();
    }

    public function logoutSession(
        LogoutSessionDTO $dto,
    ): void {
        $session = $this->repository
            ->findSessionByToken(
                $dto->tokenId
            );
        if (! $session) {
            throw new RuntimeException(
                'Session not found.'
            );
        }
        if ((string) $session->user_id !== $dto->userId) {
            throw new RuntimeException(
                'Unauthorized session.'
            );
        }
        $this->repository
            ->terminateSession(
                $dto->tokenId
            );
        event(
            new SessionTerminated(
                $session->fresh()
            )
        );
    }

    public function sessions(
        string $userId,
    ): Collection {
        return $this->repository
            ->activeSessions(
                $userId
            );
    }

    public function logoutOtherSessions(
        LogoutOtherSessionsDTO $dto,
    ): void {
        $this->repository
            ->terminateOtherSessions(
                $dto->userId,
                $dto->currentTokenId
            );
    }
}