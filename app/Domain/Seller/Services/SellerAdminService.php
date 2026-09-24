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
use App\Domain\Seller\Exceptions\DuplicateStoreNameException;
use App\Domain\Seller\Exceptions\InvalidStoreNameChangeException;
use App\Domain\Seller\Repositories\Contracts\SellerApplicationRepositoryInterface;
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
        private SellerTeamService $team,
        private SellerApplicationRepositoryInterface $applications,
    ) {}

    public function list(
        ?SellerProfileStatus $status,
        ?BankVerificationStatus $bank,
        int $perPage,
        bool $namePendingOnly = false,
    ): LengthAwarePaginator {
        return $this->profiles->paginateForAdmin($status, $bank, $perPage, $namePendingOnly);
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

            // C40 — was `=== SUSPENDED`. That was correct while ACTIVE and
            // SUSPENDED were the only states; with CLOSED it would have let
            // an admin suspend a closed store and silently wipe the fact
            // that its owner had walked away.
            if (! $profile->status->isOpen()) {
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

            // C40 — was `=== ACTIVE`. A CLOSED store would have passed that
            // check and been flipped to active through the WRONG door:
            // closed_at left behind, and the team's roles never restored.
            // Reopening a closed store is reopen(), not reactivate().
            if ($profile->status !== SellerProfileStatus::SUSPENDED) {
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

    /**
     * P8-6 — an admin grants a closed store's request to come back.
     * Deliberately NOT reactivate(): different columns to clear, and the
     * team's access has to be handed back.
     */
    public function reopen(int $sellerProfileId, int $adminId): SellerProfile
    {
        return DB::transaction(function () use ($sellerProfileId, $adminId): SellerProfile {
            $profile = $this->lock($sellerProfileId, $adminId);

            if (! $profile->isClosed()) {
                throw InvalidSellerStatusTransitionException::cannot('reopened', $profile->status);
            }

            $profile = $this->profiles->update($profile, [
                'status' => SellerProfileStatus::ACTIVE->value,
                'closed_at' => null,
                'closure_reason' => null,
                'reopen_requested_at' => null,
            ]);

            // C31 pays off here: the team rows were never deleted, so the
            // exact team that existed before comes straight back.
            $this->team->restoreStoreRoles((int) $profile->id);

            return $profile;
        });
    }

    /**
     * P9-4 — approve or refuse a rename.
     *
     * @return array{profile: SellerProfile, previous_name: string, approved: bool, reason: ?string}
     */
    public function reviewNameChange(int $sellerProfileId, int $adminId, bool $approve, ?string $reason): array
    {
        return DB::transaction(function () use ($sellerProfileId, $adminId, $approve, $reason): array {
            $profile = $this->lock($sellerProfileId, $adminId);

            if (! $profile->hasNameChangePending()) {
                throw InvalidStoreNameChangeException::nothingPending($sellerProfileId);
            }

            $requested = (string) $profile->pending_store_name;
            $previous = (string) $profile->store_name;

            if (! $approve) {
                return [
                    'profile' => $this->profiles->update($profile, [
                        'pending_store_name' => null,
                        'store_name_requested_at' => null,
                    ]),
                    'previous_name' => $previous,
                    'approved' => false,
                    'reason' => $reason,
                ];
            }

            // C44 — the SECOND uniqueness check, inside the transaction on
            // a locked row. Between the request and this moment another
            // store can have taken the name; checking only at request time
            // is the classic race that ends with two stores sharing a name
            // and a UNIQUE index blowing up in an admin's face.
            if ($this->profiles->storeNameExists($requested, (int) $profile->id)
                || $this->applications->storeNameExists($requested)) {
                throw DuplicateStoreNameException::taken($requested);
            }

            return [
                'profile' => $this->profiles->update($profile, [
                    'store_name' => $requested,
                    'pending_store_name' => null,
                    'store_name_requested_at' => null,
                ]),
                'previous_name' => $previous,
                'approved' => true,
                'reason' => null,
            ];
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
