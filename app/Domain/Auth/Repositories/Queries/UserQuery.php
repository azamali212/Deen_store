<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

use App\Domain\Auth\Enums\UserAccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class UserQuery
{
    public function byEmail(string $email): Builder
    {
        return User::query()
            ->where('email', strtolower(trim($email)));
    }

    public function byId(int|string $id): Builder
    {
        return User::query()
            ->whereKey($id);
    }

    public function byIdWithTrashed(int|string $id): Builder
    {
        return User::withTrashed()
            ->whereKey($id);
    }

    public function byUuid(string $uuid): Builder
    {
        return User::query()
            ->where('uuid', $uuid);
    }

    public function byPhone(string $phone): Builder
    {
        return User::query()
            ->where('phone', $phone);
    }

    public function active(): Builder
    {
        return User::query()
            ->where('status', 'active');
    }

    /**
     * Builds the admin user-search query. Every filter is optional and
     * applied only when given (Eloquent's ->when()), so calling this with
     * all-null filters just returns "all users" ordered newest first.
     */
    public function search(
        ?string $search,
        ?UserAccountStatus $status,
        ?string $role,
        ?bool $emailVerified,
        ?bool $phoneVerified,
    ): Builder {
        return User::query()
            ->when(
                $search !== null,
                fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                }),
            )
            ->when(
                $status !== null,
                fn (Builder $query): Builder => $query->where('status', $status->value),
            )
            ->when(
                $role !== null,
                fn (Builder $query): Builder => $query->whereHas(
                    'roles',
                    fn (Builder $query): Builder => $query->where('name', $role),
                ),
            )
            ->when(
                $emailVerified === true,
                fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'),
            )
            ->when(
                $emailVerified === false,
                fn (Builder $query): Builder => $query->whereNull('email_verified_at'),
            )
            ->when(
                $phoneVerified === true,
                fn (Builder $query): Builder => $query->whereNotNull('phone_verified_at'),
            )
            ->when(
                $phoneVerified === false,
                fn (Builder $query): Builder => $query->whereNull('phone_verified_at'),
            )
            ->latest();
    }
}
