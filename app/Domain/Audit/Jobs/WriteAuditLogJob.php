<?php

declare(strict_types=1);

namespace App\Domain\Audit\Jobs;

use App\Domain\Audit\Actions\CreateAuditLogAction;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * The actual audit_logs DB write, run in the background.
 *
 * Listeners capture the AuditContextDTO (actor, IP, panel, request id) and
 * build the full CreateAuditLogDTO synchronously, inside the original HTTP
 * request — that context is only available while the real request is live.
 * This job just persists an already-built DTO, so queueing it never loses
 * or corrupts who-did-what data; only the (slower, DB-write) persistence
 * step moves off the request thread.
 */
final class WriteAuditLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly CreateAuditLogDTO $dto,
    ) {}

    public function handle(CreateAuditLogAction $action): void
    {
        $action->execute($this->dto);
    }
}
