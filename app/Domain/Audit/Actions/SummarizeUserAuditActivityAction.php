<?php

declare(strict_types=1);

namespace App\Domain\Audit\Actions;

use App\Domain\Audit\Services\AuditAiSummaryService;
use App\Models\User;

final readonly class SummarizeUserAuditActivityAction
{
    public function __construct(
        private AuditAiSummaryService $service,
    ) {}

    public function execute(User $user, int $days = 30): string
    {
        return $this->service->summarizeForUser($user, $days);
    }
}
