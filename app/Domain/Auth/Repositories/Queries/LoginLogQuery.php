<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Domain\Auth\Enums\AuthStatus;
use App\Domain\Auth\Enums\LoginRiskLevel;
use App\Models\LoginLog;
use Illuminate\Database\Eloquent\Builder;

final class LoginLogQuery
{
    public function forUser(int|string $userId): Builder
    {
        return LoginLog::query()
            ->where('user_id', $userId)
            ->latest();
    }

    public function failedForEmail(string $email): Builder
    {
        return LoginLog::query()
            ->where('email', strtolower(trim($email)))
            ->where('status', AuthStatus::FAILED->value)
            ->latest();
    }

    public function recentFailuresForEmail(
        string $email,
        int $minutes = 15
    ): Builder {
        return LoginLog::query()
            ->where('email', strtolower(trim($email)))
            ->where('status', AuthStatus::FAILED->value)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->latest();
    }

    public function highRisk(): Builder
    {
        return LoginLog::query()
            ->whereIn('risk_level', [
                LoginRiskLevel::HIGH->value,
                LoginRiskLevel::CRITICAL->value,
            ])
            ->latest();
    }
}