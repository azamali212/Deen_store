<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repositories\Queries;

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

    public function byUuid(string $uuid): Builder
    {
        return User::query()
            ->where('uuid', $uuid);
    }

    public function active(): Builder
    {
        return User::query()
            ->where('status', 'active');
    }
}