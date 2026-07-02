<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Domain\Auth\Enums\AuthPanel;
use App\Models\ActiveSession;
use Illuminate\Database\Eloquent\Builder;

final class SessionQuery
{
    public function activeForUser(int|string $userId): Builder
    {
        return ActiveSession::query()
            ->where('user_id', $userId)
            ->whereNull('terminated_at')
            ->latest('last_activity_at');
    }

    public function activeForPanel(
        int|string $userId,
        AuthPanel $panel
    ): Builder {
        return ActiveSession::query()
            ->where('user_id', $userId)
            ->where('panel', $panel->value)
            ->whereNull('terminated_at')
            ->latest('last_activity_at');
    }

    public function byTokenId(string $tokenId): Builder
    {
        return ActiveSession::query()
            ->where('token_id', $tokenId);
    }

    public function otherActiveSessions(int|string $userId,string $currentTokenId,): Builder 
    {
        return ActiveSession::query()

            ->where(
                'user_id',
                $userId
            )

            ->whereNull(
                'terminated_at'
            )

            ->where(
                'token_id',
                '!=',
                $currentTokenId
            );
    }
}
