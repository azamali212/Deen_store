<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\SellerKycStatus;
use App\Domain\Seller\Enums\SellerRenewalStatus;
use App\Domain\Seller\Exceptions\CannotManageOwnStoreException;
use App\Domain\Seller\Exceptions\DocumentRejectedByAiException;
use App\Domain\Seller\Exceptions\InvalidRenewalStateException;
use App\Domain\Seller\Exceptions\SellerRenewalNotFoundException;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerRenewalRepositoryInterface;
use App\Models\SellerDocumentRenewal;
use App\Models\SellerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Phase 8a — a LIVE seller replaces an expiring KYC document.
 *
 * Same two-layer shape as onboarding (P8-4): the AI reads it first, then an
 * admin confirms. Re-KYC is precisely where someone would try to paste
 * another person's document, so it does not get the lighter treatment.
 */
final readonly class SellerDocumentRenewalService
{
    public function __construct(
        private SellerRenewalRepositoryInterface $renewals,
        private SellerProfileRepositoryInterface $profiles,
        private SellerProfileService $profileService,
        private SellerKycService $kyc,
        private DocumentStorageInterface $storage,
        private DocumentVerifierInterface $verifier,
    ) {}

    public function upload(int $userId, SellerDocumentType $type, UploadedFile $file): SellerDocumentRenewal
    {
        // Store must be open AND the caller must be the owner — checked
        // BEFORE the billable AI call (C25/C35).
        $profile = $this->profileService->guardKycDocuments($userId);

        if (! $type->isRenewable()) {
            throw InvalidRenewalStateException::notRenewable($type->value);
        }

        // One pending replacement per document type: a second upload would
        // leave the admin guessing which one is current.
        if ($this->renewals->pendingForStoreAndType((int) $profile->id, $type->value) !== null) {
            throw InvalidRenewalStateException::alreadyPending($type->value);
        }

        // Layer 1 on the raw bytes, before anything touches disk.
        $verification = $this->verifier->verify(
            $type,
            (string) file_get_contents($file->getRealPath()),
            (string) ($file->getMimeType() ?: 'application/octet-stream'),
        );

        if (! $verification->isAcceptable()) {
            throw DocumentRejectedByAiException::forCategory($type, $verification->rejection);
        }

        $path = $this->storage->storeRenewal((int) $profile->id, $file);

        try {
            return $this->renewals->create([
                'seller_profile_id' => $profile->id,
                'document_type' => $type->value,
                'file_path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => (string) $file->getMimeType(),
                'size_bytes' => (int) $file->getSize(),
                'ai_status' => $verification->aiStatus()->value,
                'ai_findings' => [
                    'fields' => $verification->fields,
                    'concerns' => $verification->concerns,
                ],
                'ai_checked_at' => now(),
                // P8-1 — the ONE value lifted out of the encrypted findings.
                'extracted_expiry_date' => $this->readExpiry($type, $verification->fields),
                'status' => SellerRenewalStatus::PENDING->value,
            ]);
        } catch (Throwable $e) {
            $this->storage->delete($path);

            throw $e;
        }
    }

    public function listForUser(int $userId): Collection
    {
        $profile = $this->profileService->getForUser($userId);

        return $this->renewals->listForStore((int) $profile->id);
    }

    public function queue(int $perPage): LengthAwarePaginator
    {
        return $this->renewals->pendingQueue($perPage);
    }

    public function download(int $renewalId, int $adminId): StreamedResponse
    {
        $renewal = $this->findOrFail($renewalId);
        $this->ensureNotOwnStore($renewal, $adminId);

        if ($renewal->isPurged()) {
            throw SellerRenewalNotFoundException::fileMissing();
        }

        return $this->storage->download(
            $renewal->file_path,
            // C14 — the download name is ours, never the seller's own file
            // name; nothing user-controlled goes into the header.
            sprintf('renewal-%d-%s.%s', $renewal->id, $renewal->document_type->value, $this->extensionFor($renewal)),
        );
    }

    /**
     * @return array{renewal: SellerDocumentRenewal, profile: SellerProfile, previous_status: SellerKycStatus}
     */
    public function review(int $renewalId, int $adminId, bool $approve, ?string $reason): array
    {
        return DB::transaction(function () use ($renewalId, $adminId, $approve, $reason): array {
            // C13 — two admins clicking at the same moment: the second
            // waits, then sees the new status and gets a 409.
            $renewal = $this->renewals->lockPendingById($renewalId)
                ?? throw SellerRenewalNotFoundException::withId($renewalId);

            if (! $renewal->isPending()) {
                throw InvalidRenewalStateException::alreadyReviewed($renewalId);
            }

            $this->ensureNotOwnStore($renewal, $adminId);

            $profile = $renewal->sellerProfile;
            $previousStatus = $profile->kycStatus();

            $renewal = $this->renewals->update($renewal, [
                'status' => ($approve ? SellerRenewalStatus::APPROVED : SellerRenewalStatus::REJECTED)->value,
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'rejection_reason' => $approve ? null : $reason,
            ]);

            if ($approve) {
                $column = $renewal->document_type->expiryColumn();

                // cnic_back carries no date of its own — approving it just
                // records the new scan; the date came with cnic_front.
                if ($column !== null && $renewal->extracted_expiry_date !== null) {
                    $profile = $this->profiles->update($profile, [
                        $column => $renewal->extracted_expiry_date,
                    ]);
                }

                // Recompute from the new dates, THEN clear the "already
                // warned" mark, so the next expiry cycle warns again from
                // scratch instead of staying silent (C29).
                $profile = $this->kyc->refresh($profile)['profile'];
                $profile = $this->profiles->update($profile, ['kyc_notified_at' => null]);
            }

            return ['renewal' => $renewal, 'profile' => $profile, 'previous_status' => $previousStatus];
        });
    }

    private function findOrFail(int $renewalId): SellerDocumentRenewal
    {
        return $this->renewals->findById($renewalId)
            ?? throw SellerRenewalNotFoundException::withId($renewalId);
    }

    /**
     * C12 — an admin is also a normal user who may run their own store.
     * They never review their own documents.
     */
    private function ensureNotOwnStore(SellerDocumentRenewal $renewal, int $adminId): void
    {
        if ((int) $renewal->sellerProfile->user_id === $adminId) {
            throw CannotManageOwnStoreException::forAdmin($adminId, (int) $renewal->seller_profile_id);
        }
    }

    /**
     * @param  array<string, string|null>  $fields
     */
    private function readExpiry(SellerDocumentType $type, array $fields): ?string
    {
        $field = $type->expiryField();

        if ($field === null) {
            return null;
        }

        $value = $fields[$field] ?? null;

        // The verifier already normalises dates to YYYY-MM-DD and drops
        // anything it could not parse, so a non-matching value is dropped
        // here rather than written to a date column.
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            ? $value
            : null;
    }

    private function extensionFor(SellerDocumentRenewal $renewal): string
    {
        return match ($renewal->mime_type) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            default => 'jpg',
        };
    }
}