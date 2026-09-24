<?php

declare(strict_types=1);

namespace App\Domain\Audit\Listeners;

use App\Domain\Audit\DTO\AuditContextDTO;
use App\Domain\Audit\DTO\CreateAuditLogDTO;
use App\Domain\Audit\Enums\AuditAction;
use App\Domain\Audit\Enums\AuditCategory;
use App\Domain\Audit\Enums\AuditSeverity;
use App\Domain\Audit\Enums\AuditStatus;
use App\Domain\Audit\Jobs\WriteAuditLogJob;
use App\Domain\Seller\Enums\DocumentRejectionCategory;
use App\Domain\Seller\Events\SellerDocumentRejectedByAi;
use App\Models\User;

final class AuditSellerDocumentRejectedByAiListener
{
    public function handle(SellerDocumentRejectedByAi $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_DOCUMENT_REJECTED_BY_AI,
                category: AuditCategory::SELLER_MANAGEMENT,
                // Uploading explicit content as a KYC document is abuse ->
                // WARNING. A blurry photo is an honest mistake -> NOTICE.
                severity: $event->category === DocumentRejectionCategory::EXPLICIT
                    ? AuditSeverity::WARNING
                    : AuditSeverity::NOTICE,
                status: AuditStatus::DENIED,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $event->userId,
                description: 'Seller document upload blocked by automated verification.',
                newValues: [
                    'application_id' => $event->applicationId,
                    'document_type' => $event->documentType->value,
                    'reason' => $event->category->value,
                ],
                occurredAt: now(),
            ),
        );
    }
}
