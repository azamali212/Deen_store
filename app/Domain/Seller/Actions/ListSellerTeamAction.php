<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerTeamService;
use Illuminate\Support\Collection;

final readonly class ListSellerTeamAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    // Any active member may see who else is in the store.
    public function execute(int $userId): Collection
    {
        return $this->service->list($userId);
    }
}
