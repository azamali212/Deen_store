<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Events\PasswordHistoryCreated;
use App\Domain\Auth\Exceptions\PasswordPreviouslyUsedException;
use App\Domain\Auth\Repositories\Contracts\PasswordHistoryRepositoryInterface;
use App\Domain\Auth\Repositories\DTO\CreatePasswordHistoryData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class PasswordHistoryService
{
    public function __construct(
        private PasswordHistoryRepositoryInterface $repository,
    ) {}

    public function ensurePasswordHasNotBeenUsedBefore(
        User $user,
        string $newPassword,
        int $historyLimit,
    ): void {

        $histories = $this->repository
            ->latestForUser(
                userId: (string) $user->id,
                limit: $historyLimit,
            );

        foreach ($histories as $history) {

            if (Hash::check(
                $newPassword,
                $history->password,
            )) {
                throw PasswordPreviouslyUsedException::create();
            }
        }
    }

    public function storePassword(
        User $user,
        string $hashedPassword,
    ): void {

        $history = $this->repository
            ->create(
                new CreatePasswordHistoryData(
                    userId: (string) $user->id,
                    password: $hashedPassword,
                ),
            );

        event(
            new PasswordHistoryCreated(
                $history,
            ),
        );

        $this->cleanupOldHistory(
            (string) $user->id,
        );
    }

    private function cleanupOldHistory(
        string $userId,
    ): void {

        $limit = (int) config(
            'auth_security.password_history.remember',
            5,
        );

        while (
            $this->repository->countByUser(
                $userId,
            ) > $limit
        ) {

            $oldest = $this->repository
                ->oldestForUser(
                    $userId,
                );

            if (! $oldest) {
                break;
            }

            $this->repository
                ->delete(
                    $oldest,
                );
        }
    }
}
