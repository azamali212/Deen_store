<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Services\SessionService;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListSessionsAction
{
    public function __construct(
        private SessionService $service,
    ) {}

    public function execute(
        string $userId,
    ): Collection {

        return $this->service
            ->sessions(
                $userId
            );
    }
}