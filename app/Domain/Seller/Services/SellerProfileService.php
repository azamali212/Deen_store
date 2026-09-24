<?php

declare(strict_types=1);

namespace App\Domain\Seller\Services;

use App\Domain\Seller\Contracts\DocumentStorageInterface;
use App\Domain\Seller\Contracts\DocumentVerifierInterface;
use App\Domain\Seller\Contracts\LogoStorageInterface;
use App\Domain\Seller\DTO\UpdateSellerProfileDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Exceptions\DocumentRejectedByAiException;
use App\Domain\Seller\Exceptions\InvalidBankVerificationStateException;
use App\Domain\Seller\Enums\SellerTeamRole;
use App\Domain\Seller\Exceptions\SellerProfileNotFoundException;
use App\Domain\Seller\Exceptions\SellerStoreSuspendedException;
use App\Domain\Seller\Repositories\Contracts\SellerProfileRepositoryInterface;
use App\Models\SellerProfile;
use App\Models\SellerTeamMember;
use Illuminate\Http\UploadedFile;
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

        $this->ensureNotSuspended($membership->sellerProfile);

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

        $this->ensureNotSuspended($membership->sellerProfile);
        $this->team->ensureCan($membership, $check, $action);

        return $membership->sellerProfile;
    }

    private function ensureNotSuspended(SellerProfile $profile): void
    {
        if ($profile->isSuspended()) {
            throw SellerStoreSuspendedException::forProfile($profile->id);
        }
    }

    public function logoUrl(SellerProfile $profile): ?string
    {
        return $profile->logo_path !== null
            ? $this->logos->url($profile->logo_path)
            : null;
    }
}
