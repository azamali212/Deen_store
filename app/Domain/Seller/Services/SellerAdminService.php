<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\DTO\ReviewBankProofDTO;
use App\Domain\Seller\DTO\SuspendSellerDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Exceptions\CannotManageOwnStoreException;
use App\Domain\Seller\Exceptions\InvalidBankVerificationStateException;
use App\Domain\Seller\Exceptions\InvalidSellerStatusTransitionException;
use App\Domain\Seller\Exceptions\SellerProfileNotFoundByAdminException;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Models\SellerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phase 6 — what an admin can do to a LIVE store.
 *
 * Every state change runs in a transaction on a locked row (C13), and an
 * admin may never act on their own store (C12's rule, P6-1).
 */
final readonly class SellerAdminService
{
    public function __construct(
        private SellerProfileRepositoryInterface $profiles,
        private DocumentStorageInterface $storage,
    ) {}

    public function list(
        ?SellerProfileStatus $status,
        ?BankVerificationStatus $bank,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->profiles->paginateForAdmin($status, $bank, $perPage);
    }

    public function get(int $sellerProfileId): SellerProfile
    {
        $profile = $this->profiles->findById($sellerProfileId)
            ?? throw SellerProfileNotFoundByAdminException::withId($sellerProfileId);

        return $profile->load(['user:id,name,email', 'suspendedBy:id,name,email', 'bankVerifiedBy:id,name,email']);
    }

    public function suspend(int $sellerProfileId, SuspendSellerDTO $dto): SellerProfile
    {
        return DB::transaction(function () use ($sellerProfileId, $dto): SellerProfile {
            $profile = $this->lock($sellerProfileId, $dto->adminId);

            if ($profile->status === SellerProfileStatus::SUSPENDED) {
                throw InvalidSellerStatusTransitionException::cannot('suspended', $profile->status);
            }

            return $this->profiles->update($profile, [
                'status' => SellerProfileStatus::SUSPENDED->value,
                'suspension_reason' => $dto->reason,
                'suspended_by' => $dto->adminId,
                'suspended_at' => now(),
            ]);
        });
    }

    public function reactivate(int $sellerProfileId, int $adminId): SellerProfile
    {
        return DB::transaction(function () use ($sellerProfileId, $adminId): SellerProfile {
            $profile = $this->lock($sellerProfileId, $adminId);

            if ($profile->status === SellerProfileStatus::ACTIVE) {
                throw InvalidSellerStatusTransitionException::cannot('reactivated', $profile->status);
            }

            // The reason is cleared: it belonged to the suspension that
            // just ended. The audit log keeps the history.
            return $this->profiles->update($profile, [
                'status' => SellerProfileStatus::ACTIVE->value,
                'suspension_reason' => null,
                'suspended_by' => null,
                'suspended_at' => null,
            ]);
        });
    }

    public function downloadBankProof(int $sellerProfileId): StreamedResponse
    {
        $profile = $this->get($sellerProfileId);

        if ($profile->bank_proof_path === null) {
            throw InvalidBankVerificationStateException::nothingToReview($sellerProfileId);
        }

        $extension = strtolower((string) pathinfo((string) $profile->bank_proof_original_name, PATHINFO_EXTENSION));
        $extension = preg_match('/^[a-z0-9]{1,5}$/', $extension) === 1 ? $extension : 'bin';

        // Our own safe name (C14), never the seller's original file name.
        return $this->storage->download(
            $profile->bank_proof_path,
            "store-{$profile->id}-bank-proof.{$extension}",
        );
    }

    /**
     * P6-2 — verify -> admin_verified, or reject -> back to mismatch.
     */
    public function reviewBankProof(int $sellerProfileId, ReviewBankProofDTO $dto): SellerProfile
    {
        return DB::transaction(function () use ($sellerProfileId, $dto): SellerProfile {
            $profile = $this->lock($sellerProfileId, $dto->adminId);

            if (! $profile->hasBankProofPending()) {
                throw InvalidBankVerificationStateException::nothingToReview($sellerProfileId);
            }

            if ($dto->isVerification()) {
                return $this->profiles->update($profile, [
                    'bank_verification_status' => BankVerificationStatus::ADMIN_VERIFIED->value,
                    'bank_verified_by' => $dto->adminId,
                    'bank_verified_at' => now(),
                    'bank_rejection_reason' => null,
                ]);
            }

            // Rejected: the proof file is kept so the audit trail still has
            // what was reviewed; the seller uploads a new one to try again.
            return $this->profiles->update($profile, [
                'bank_verification_status' => BankVerificationStatus::MISMATCH->value,
                'bank_verified_by' => $dto->adminId,
                'bank_verified_at' => now(),
                'bank_rejection_reason' => $dto->reason,
            ]);
        });
    }

    private function lock(int $sellerProfileId, int $adminId): SellerProfile
    {
        $profile = $this->profiles->lockById($sellerProfileId)
            ?? throw SellerProfileNotFoundByAdminException::withId($sellerProfileId);

        if ((int) $profile->user_id === $adminId) {
            throw CannotManageOwnStoreException::forAdmin($adminId, $sellerProfileId);
        }

        return $profile;
    }
}
