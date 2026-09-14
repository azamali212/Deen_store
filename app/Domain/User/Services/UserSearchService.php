<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Domain\User\DTO\UserFilterDTO;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class UserSearchService
{
    public function __construct(
        private AuthRepositoryInterface $users,
    ) {}

    public function search(UserFilterDTO $filters): LengthAwarePaginator
    {
        return $this->users->searchUsers(
            search: $filters->search,
            status: $filters->status,
            role: $filters->role,
            emailVerified: $filters->emailVerified,
            phoneVerified: $filters->phoneVerified,
            perPage: $filters->perPage,
        );
    }
}
