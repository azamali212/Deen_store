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
use App\Domain\Seller\Events\SellerDocumentUploaded;
use App\Models\User;

final class AuditSellerDocumentUploadedListener
{
    public function handle(SellerDocumentUploaded $event): void
    {
        $context = request()->hasSession() || app()->runningInConsole() === false
            ? AuditContextDTO::fromRequest(request())
            : AuditContextDTO::system();

        $document = $event->document->loadMissing('application');

        WriteAuditLogJob::dispatch(
            new CreateAuditLogDTO(
                action: AuditAction::SELLER_DOCUMENT_UPLOADED,
                category: AuditCategory::SELLER_MANAGEMENT,
                severity: AuditSeverity::INFO,
                status: AuditStatus::SUCCESS,
                context: $context,
                subjectType: User::class,
                subjectId: (string) $document->application->user_id,
                description: 'A seller application document was uploaded.',
                // Never the file path (BLUEPRINT section 6).
                newValues: [
                    'application_id' => $document->seller_application_id,
                    'document_type' => $document->document_type->value,
                    'mime_type' => $document->mime_type,
                    'size_bytes' => $document->size_bytes,
                    'replaced_existing' => ! $document->wasRecentlyCreated,
                ],
                occurredAt: now(),
            ),
        );
    }
}
