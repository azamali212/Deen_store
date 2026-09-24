<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Seller\Services\SellerTeamService;
use Illuminate\Support\Collection;

final readonly class ListMyInvitationsAction
{
    public function __construct(
        private SellerTeamService $service,
    ) {}

    public function execute(int $userId): Collection
    {
        return $this->service->pendingInvitations($userId);
    }
}
