<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\Contracts\LogoStorageInterface;
use App\Domain\Seller\DTO\UpdateSellerProfileDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Enums\SellerProfileStatus;
use App\Domain\Seller\Exceptions\DocumentRejectedByAiException;
use App\Domain\Seller\Exceptions\InvalidBankVerificationStateException;
use App\Domain\Seller\Exceptions\InvalidSellerStatusTransitionException;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Exceptions\SellerStoreClosedException;
use App\Domain\Seller\Exceptions\SellerStoreSuspendedException;
use App\Domain\Seller\Exceptions\StoreClosureNotConfirmedException;
use App\Domain\Seller\Exceptions\DuplicateStoreNameException;
use App\Domain\Seller\Exceptions\InvalidStoreNameChangeException;
use App\Domain\Seller\Repositories\Contracts\SellerApplicationRepositoryInterface;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class SellerProfileService
{
    public function __construct(
        private SellerProfileRepositoryInterface $profiles,
        private LogoStorageInterface $logos,
        private SellerDocumentVerificationService $verification,
        private DocumentStorageInterface $documents,
        private DocumentVerifierInterface $verifier,
        private SellerTeamService $team,
        private SellerApplicationRepositoryInterface $applications,
    ) {}

    /**
     * Phase 7 — resolved through the team membership, NOT through
     * seller_profiles.user_id (which only ever knew the owner).
     */
    public function getForUser(int $userId): SellerProfile
    {
        return $this->membershipFor($userId)->sellerProfile;
    }

    public function membershipFor(int $userId): SellerTeamMember
    {
        return $this->team->membershipFor($userId);
    }

    /**
     * Saves only what actually changed and reports back WHAT changed, so
     * the action can fire the right events:
     *   - changed_fields: non-bank fields (-> SellerProfileUpdated)
     *   - bank_change:    null, or old/new last4 + bank name
     *                     (-> SellerBankDetailsChanged, C7)
     *
     * @return array{profile: SellerProfile, changed_fields: array<int, string>, bank_change: array<string, mixed>|null}
     */
    /**
     * Everything update() refuses on, in one place: the store must be open
     * and the member's role must allow what the DTO is asking for.
     *
     * Public so UpdateSellerProfileAction can run it BEFORE paying for an AI
     * moderation call on text we were going to refuse anyway — a suspended
     * store or a staff member editing the storefront must cost nothing.
     * update() runs it again, so the service is still safe on its own.
     */
    public function guardUpdate(int $userId, UpdateSellerProfileDTO $dto): SellerTeamMember
    {
        $membership = $this->membershipFor($userId);

        $this->ensureStoreOpen($membership->sellerProfile);

        if ($dto->profileFields !== []) {
            $this->team->ensureCan(
                $membership,
                fn (SellerTeamRole $role): bool => $role->canEditStoreProfile(),
                'edit the store profile',
            );
        }

        if ($dto->hasBankDetails()) {
            $this->team->ensureCan(
                $membership,
                fn (SellerTeamRole $role): bool => $role->canManageBankDetails(),
                'change the payout bank details',
            );
        }

        return $membership;
    }

    public function update(int $userId, UpdateSellerProfileDTO $dto): array
    {
        $profile = $this->guardUpdate($userId, $dto)->sellerProfile;

        $attributes = [];

        foreach ($dto->profileFields as $column => $value) {
            if ($profile->{$column} !== $value) {
                $attributes[$column] = $value;
            }
        }

        $changedFields = array_keys($attributes);

        $bankChange = null;

        if ($dto->hasBankDetails()) {
            // $profile->bank_account_number is transparently DECRYPTED by the
            // `encrypted` cast, so this is a plain-text comparison in memory.
            $bankIsDifferent = $profile->bank_account_number !== $dto->bankAccountNumber
                || $profile->bank_name !== $dto->bankName
                || $profile->bank_account_title !== $dto->bankAccountTitle;

            if ($bankIsDifferent) {
                $newLast4 = substr((string) $dto->bankAccountNumber, -4);

                // P5-2 — compare with the bank statement verified during
                // onboarding. A mismatch is SAVED but flagged (decided).
                $verificationStatus = $this->verification->matchPayoutBank(
                    $profile,
                    $newLast4,
                    $dto->bankAccountTitle,
                );

                $bankChange = [
                    'verification_status' => $verificationStatus,
                    'is_first_time' => $profile->bank_account_number === null,
                    'old_last4' => $profile->bank_account_last4,
                    'new_last4' => $newLast4,
                    'old_bank_name' => $profile->bank_name,
                    'new_bank_name' => $dto->bankName,
                ];

                $attributes += [
                    'bank_account_title' => $dto->bankAccountTitle,
                    'bank_name' => $dto->bankName,
                    'bank_account_number' => $dto->bankAccountNumber, // encrypted on save
                    'bank_account_last4' => $newLast4,
                    'bank_verification_status' => $verificationStatus->value,
                    // P6-2: a new account starts over — an earlier proof and
                    // an earlier admin verification belonged to the OLD
                    // account and must never carry over to this one.
                    'bank_proof_path' => null,
                    'bank_proof_original_name' => null,
                    'bank_proof_uploaded_at' => null,
                    'bank_verified_by' => null,
                    'bank_verified_at' => null,
                    'bank_rejection_reason' => null,
                ];
            }
        }

        $replacedProofPath = $bankChange !== null ? $profile->bank_proof_path : null;

        if ($attributes !== []) {
            $profile = $this->profiles->update($profile, $attributes);
        }

        if ($replacedProofPath !== null) {
            $this->documents->delete($replacedProofPath);
        }

        return [
            'profile' => $profile,
            'changed_fields' => $changedFields,
            'bank_change' => $bankChange,
        ];
    }

    /**
     * P9-4 — C6 locked store_name at approval, which was right: the admin
     * verified it. But "forever" was wrong — businesses rebrand, and the
     * only route was to close and start over. The seller asks; an admin
     * approves.
     *
     * C45 — nothing user-visible changes while it is pending. The old name
     * stays live until the moment it is approved.
     */
    public function requestNameChange(int $userId, string $storeName): SellerProfile
    {
        $profile = $this->authorise(
            $userId,
            fn (SellerTeamRole $role): bool => $role->canRenameStore(),
            'change the store name',
        );

        if ($profile->hasNameChangePending()) {
            throw InvalidStoreNameChangeException::alreadyPending();
        }

        if ($storeName === $profile->store_name) {
            throw InvalidStoreNameChangeException::sameAsCurrent();
        }

        // C44, first of two checks. The second runs inside the approval
        // transaction, because another store can take this name in between.
        $this->ensureStoreNameIsFree($storeName, (int) $profile->id);

        return $this->profiles->update($profile, [
            'pending_store_name' => $storeName,
            'store_name_requested_at' => now(),
        ]);
    }

    public function withdrawNameChange(int $userId): SellerProfile
    {
        $profile = $this->authorise(
            $userId,
            fn (SellerTeamRole $role): bool => $role->canRenameStore(),
            'change the store name',
        );

        if (! $profile->hasNameChangePending()) {
            throw InvalidStoreNameChangeException::nothingPending((int) $profile->id);
        }

        return $this->profiles->update($profile, [
            'pending_store_name' => null,
            'store_name_requested_at' => null,
        ]);
    }

    /**
     * A name must be free in BOTH tables. Every approved store also has a
     * frozen application row carrying the name it was approved under, and
     * that row is the record of what the admin actually verified — so it
     * keeps its claim on that name.
     */
    private function ensureStoreNameIsFree(string $storeName, ?int $exceptProfileId = null): void
    {
        if ($this->profiles->storeNameExists($storeName, $exceptProfileId)
            || $this->applications->storeNameExists($storeName)) {
            throw DuplicateStoreNameException::taken($storeName);
        }
    }

    /**
     * P8-5 — the seller's own exit.
     *
     * Goes through authorise(), so a SUSPENDED store is refused (C30):
     * closing must never become the escape hatch from an investigation.
     * An already-closed store is refused by the same guard.
     */
    public function close(int $userId, string $confirmStoreName, ?string $reason): SellerProfile
    {
        return DB::transaction(function () use ($userId, $confirmStoreName, $reason): SellerProfile {
            $profile = $this->authorise(
                $userId,
                fn (SellerTeamRole $role): bool => $role->canCloseStore(),
                'close the store',
            );

            // C34 — a one-click button that dissolves a business is a bug.
            if (trim($confirmStoreName) !== $profile->store_name) {
                throw StoreClosureNotConfirmedException::nameDoesNotMatch();
            }

            $profile = $this->profiles->update($profile, [
                'status' => SellerProfileStatus::CLOSED->value,
                'closed_at' => now(),
                'closure_reason' => $reason,
                'reopen_requested_at' => null,
            ]);

            // C31/C39 — access goes, rows stay, the owner keeps their role
            // so they can still ask for the store back.
            $this->team->stripStoreRoles((int) $profile->id);

            return $profile;
        });
    }

    /**
     * P8-6 — the seller asks; an admin decides. No new application and no
     * re-uploading everything, but also no closing to duck a review and
     * quietly reopening later.
     */
    public function requestReopen(int $userId): SellerProfile
    {
        $membership = $this->membershipFor($userId);
        $profile = $membership->sellerProfile;

        // NOT authorise(): that refuses a closed store, and closed is
        // exactly the state this method exists for.
        $this->team->ensureCan(
            $membership,
            fn (SellerTeamRole $role): bool => $role->canCloseStore(),
            'reopen the store',
        );

        if (! $profile->isClosed()) {
            throw InvalidSellerStatusTransitionException::cannot('reopened', $profile->status);
        }

        if ($profile->reopen_requested_at !== null) {
            throw InvalidSellerStatusTransitionException::reopenAlreadyRequested();
        }

        return $this->profiles->update($profile, ['reopen_requested_at' => now()]);
    }

    /**
     * Phase 8a — a KYC renewal replaces the owner's own identity document,
     * so only the owner may upload one, and never while the store is shut.
     * Runs BEFORE the billable AI call, like every other guard (C25/C35).
     */
    public function guardKycDocuments(int $userId): SellerProfile
    {
        return $this->authorise(
            $userId,
            fn (SellerTeamRole $role): bool => $role->canManageKycDocuments(),
            'manage identity documents',
        );
    }

    /**
     * C25 — same reason as guardUpdate(): UploadSellerLogoAction sends the
     * raw image bytes to a billable AI moderation call, so a suspended
     * store or a staff member must be refused BEFORE that call is made.
     * uploadLogo() runs the same guard again on its own.
     */
    public function guardLogoChange(int $userId): void
    {
        $this->authorise($userId, fn (SellerTeamRole $role): bool => $role->canEditStoreProfile(), 'change the store logo');
    }

    public function uploadLogo(int $userId, UploadedFile $file): SellerProfile
    {
        $profile = $this->authorise($userId, fn (SellerTeamRole $role): bool => $role->canEditStoreProfile(), 'change the store logo');
        $oldPath = $profile->logo_path;

        $newPath = $this->logos->store($profile->id, $file);

        try {
            $profile = $this->profiles->update($profile, ['logo_path' => $newPath]);
        } catch (Throwable $e) {
            $this->logos->delete($newPath);

            throw $e;
        }

        // Same order as documents (C8): old file goes only after the new
        // path is safely saved.
        if ($oldPath !== null && $oldPath !== $newPath) {
            $this->logos->delete($oldPath);
        }

        return $profile;
    }

    public function deleteLogo(int $userId): SellerProfile
    {
        $profile = $this->authorise($userId, fn (SellerTeamRole $role): bool => $role->canEditStoreProfile(), 'change the store logo');

        if ($profile->logo_path === null) {
            return $profile;
        }

        $oldPath = $profile->logo_path;

        $profile = $this->profiles->update($profile, ['logo_path' => null]);

        $this->logos->delete($oldPath);

        return $profile;
    }

    /**
     * P6-2 — the seller proves the payout account is theirs by uploading a
     * statement for it. Same Layer 1 AI check as onboarding documents; if
     * the AI can read a matching account, the status flips to `matched`
     * with no admin needed, otherwise it goes to `pending_review`.
     *
     * @return array{profile: SellerProfile, auto_matched: bool}
     */
    public function uploadBankProof(int $userId, UploadedFile $file): array
    {
        $profile = $this->authorise($userId, fn (SellerTeamRole $role): bool => $role->canManageBankDetails(), 'manage the payout bank details');

        if ($profile->bank_account_last4 === null) {
            throw InvalidBankVerificationStateException::noBankAccount();
        }

        $current = $profile->bank_verification_status;

        if ($current?->isPayoutReady() === true) {
            throw InvalidBankVerificationStateException::alreadyVerified();
        }

        if ($current === BankVerificationStatus::PENDING_REVIEW) {
            throw InvalidBankVerificationStateException::awaitingReview();
        }

        // Checked BEFORE the file is stored — a rejected upload never
        // touches disk (same order as onboarding uploads).
        $verification = $this->verifier->verify(
            SellerDocumentType::BANK_STATEMENT,
            (string) file_get_contents($file->getRealPath()),
            (string) ($file->getMimeType() ?: 'application/octet-stream'),
        );

        if (! $verification->isAcceptable()) {
            throw DocumentRejectedByAiException::forCategory(
                SellerDocumentType::BANK_STATEMENT,
                $verification->rejection,
            );
        }

        $autoMatched = ! $verification->skipped
            && ($verification->fields['account_last4'] ?? null) === $profile->bank_account_last4
            && $this->verification->titlesMatch(
                $verification->fields['account_title'] ?? null,
                $profile->bank_account_title,
            );

        $oldPath = $profile->bank_proof_path;
        $newPath = $this->documents->storeBankProof($profile->id, $file);

        try {
            $profile = $this->profiles->update($profile, [
                'bank_proof_path' => $newPath,
                'bank_proof_original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'bank_proof_uploaded_at' => now(),
                'bank_verification_status' => $autoMatched
                    ? BankVerificationStatus::MATCHED->value
                    : BankVerificationStatus::PENDING_REVIEW->value,
                // A fresh proof clears the previous review outcome.
                'bank_verified_by' => null,
                'bank_verified_at' => null,
                'bank_rejection_reason' => null,
            ]);
        } catch (Throwable $e) {
            $this->documents->delete($newPath);

            throw $e;
        }

        if ($oldPath !== null && $oldPath !== $newPath) {
            $this->documents->delete($oldPath);
        }

        return ['profile' => $profile, 'auto_matched' => $autoMatched];
    }

    /**
     * Resolve the store, check the store is open, and check this member's
     * role allows the action — the three guards every write shares.
     *
     * @param  callable(SellerTeamRole): bool  $check
     */
    private function authorise(int $userId, callable $check, string $action): SellerProfile
    {
        $membership = $this->membershipFor($userId);

        $this->ensureStoreOpen($membership->sellerProfile);
        $this->team->ensureCan($membership, $check, $action);

        return $membership->sellerProfile;
    }

    /**
     * C33 — renamed from ensureNotSuspended() when closure arrived. Every
     * write guard already called this one method, so they all picked up
     * the new state for free. Had each guard asked isSuspended() for
     * itself, one would have been missed and a CLOSED store would still
     * be editable.
     *
     * The two states get DIFFERENT messages on purpose: a suspension is
     * appealed, a closure is reopened on request.
     */
    private function ensureStoreOpen(SellerProfile $profile): void
    {
        if ($profile->isSuspended()) {
            throw SellerStoreSuspendedException::forProfile($profile->id);
        }

        if ($profile->isClosed()) {
            throw SellerStoreClosedException::forProfile($profile->id);
        }
    }

    public function logoUrl(SellerProfile $profile): ?string
    {
        return $profile->logo_path !== null
            ? $this->logos->url($profile->logo_path)
            : null;
    }
}
