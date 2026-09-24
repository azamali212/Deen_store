<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\DTO\UploadApplicationDocumentDTO;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Events\SellerDocumentRejectedByAi;
use App\Domain\Seller\Exceptions\DocumentRejectedByAiException;
use App\Domain\Seller\Exceptions\InvalidApplicationStatusTransitionException;
use App\Domain\Seller\Exceptions\MissingRequiredDocumentsException;
use App\Domain\Seller\Exceptions\SellerApplicationNotFoundException;
use App\Domain\Seller\Exceptions\SellerDocumentNotFoundException;
use App\Domain\Seller\Repositories\Contracts\SellerApplicationRepositoryInterface;
use App\Models\SellerApplication;
use App\Models\SellerApplicationDocument;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final readonly class SellerDocumentService
{
    public function __construct(
        private SellerApplicationRepositoryInterface $applications,
        private DocumentStorageInterface $storage,
        private DocumentVerifierInterface $verifier,
    ) {}

    public function upload(SellerApplication $application, UploadApplicationDocumentDTO $dto): SellerApplicationDocument
    {
        // C2: no swapping a CNIC while pending or after approval.
        if (! $application->status->isEditable()) {
            throw InvalidApplicationStatusTransitionException::cannot('upload documents to', $application->status);
        }

        // Layer 1 (BLUEPRINT section 10) — checked on the raw bytes BEFORE
        // anything is written to disk. A rejected file is never stored.
        // (After the status check above, so a locked application never
        // costs an AI call.)
        $verification = $this->verifier->verify(
            $dto->documentType,
            (string) file_get_contents($dto->file->getRealPath()),
            (string) ($dto->file->getMimeType() ?: 'application/octet-stream'),
        );

        if (! $verification->isAcceptable()) {
            event(new SellerDocumentRejectedByAi(
                applicationId: $application->id,
                userId: (int) $application->user_id,
                documentType: $dto->documentType,
                category: $verification->rejection,
            ));

            throw DocumentRejectedByAiException::forCategory($dto->documentType, $verification->rejection);
        }

        $existing = $this->applications->findDocument($application, $dto->documentType);

        $newPath = $this->storage->store($application->id, $dto->file);

        try {
            $document = $this->applications->saveDocument($application, $dto->documentType, [
                'file_path' => $newPath,
                'original_name' => mb_substr($dto->file->getClientOriginalName(), 0, 255),
                // Server-detected from the file's content, not the
                // client-supplied Content-Type header.
                'mime_type' => (string) $dto->file->getMimeType(),
                'size_bytes' => (int) $dto->file->getSize(),
                // A re-upload replaces the previous AI findings too.
                'ai_status' => $verification->aiStatus()->value,
                'ai_findings' => [
                    'fields' => $verification->fields,
                    'concerns' => $verification->concerns,
                ],
                'ai_checked_at' => now(),
            ]);
        } catch (Throwable $e) {
            // DB write failed — don't leave an orphaned file behind.
            $this->storage->delete($newPath);

            throw $e;
        }

        // C8: the replaced file is deleted only AFTER the new row is saved,
        // so a failure can never leave the application with no file at all.
        if ($existing !== null && $existing->file_path !== $newPath) {
            $this->storage->delete($existing->file_path);
        }

        return $document;
    }

    /**
     * @return array<int, string>
     */
    public function missingTypes(SellerApplication $application): array
    {
        return array_values(array_diff(
            SellerDocumentType::required(),
            $this->applications->uploadedDocumentTypes($application),
        ));
    }

    public function ensureAllRequiredPresent(SellerApplication $application): void
    {
        $missing = $this->missingTypes($application);

        if ($missing !== []) {
            throw MissingRequiredDocumentsException::forApplication($application->id, $missing);
        }
    }

    /**
     * Admin-only (the route is behind role:super_admin|platform_admin).
     * The download name is built by us — "application-12-cnic_front.pdf" —
     * never the customer's original file name, so nothing user-controlled
     * ends up in the Content-Disposition header.
     */
    public function download(int $applicationId, SellerDocumentType $type): StreamedResponse
    {
        $application = $this->applications->findById($applicationId)
            ?? throw SellerApplicationNotFoundException::withId($applicationId);

        $document = $this->applications->findDocument($application, $type)
            ?? throw SellerDocumentNotFoundException::forType($applicationId, $type);

        if ($document->isPurged()) {
            throw SellerDocumentNotFoundException::purged();
        }

        $extension = strtolower((string) pathinfo($document->original_name, PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{1,5}$/', $extension) === 1 ? $extension : 'bin';

        return $this->storage->download(
            $document->file_path,
            "application-{$application->id}-{$type->value}.{$extension}",
        );
    }
}
