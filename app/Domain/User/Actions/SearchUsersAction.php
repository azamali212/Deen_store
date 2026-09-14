<?php

declare(strict_types=1);

namespace App\Domain\User\Actions;

use App\Domain\User\DTO\UserFilterDTO;
use App\Domain\User\Services\UserSearchService;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class SearchUsersAction
{
    public function __construct(
        private UserSearchService $searchService,
    ) {}

    public function execute(UserFilterDTO $dto): LengthAwarePaginator
    {
        return $this->searchService->search($dto);
    }
}
