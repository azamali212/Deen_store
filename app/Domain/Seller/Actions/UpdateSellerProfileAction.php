<?php

declare(strict_types=1);

namespace App\Domain\Seller\Actions;

use App\Domain\Moderation\Actions\EnforceContentModerationAction;
use App\Domain\Seller\DTO\UpdateSellerProfileDTO;
use App\Domain\Seller\Enums\BankVerificationStatus;
use App\Domain\Seller\Events\SellerBankDetailsChanged;
use App\Domain\Seller\Events\SellerBankUnverified;
use App\Domain\Seller\Events\SellerProfileUpdated;
use App\Domain\Seller\Services\SellerProfileService;
use App\Models\SellerProfile;

final readonly class UpdateSellerProfileAction
{
    public function __construct(
        private SellerProfileService $service,
        private EnforceContentModerationAction $moderation,
    ) {}

    public function execute(int $userId, UpdateSellerProfileDTO $dto): SellerProfile
    {
        // C25 — permission and suspension FIRST. Moderation is a paid,
        // slow network call; a suspended store or a staff member with no
        // edit rights must be turned away before we spend one on them.
        $this->service->guardUpdate($userId, $dto);

        // Store description/address are shown publicly on the storefront —
        // same AI blocking as the user profile bio. Throws BEFORE anything
        // is saved (C16).
        if ($dto->publicText() !== []) {
            $this->moderation->enforceText($userId, $dto->publicText());
        }

        $result = $this->service->update($userId, $dto);

        if ($result['changed_fields'] !== []) {
            event(new SellerProfileUpdated($result['profile'], $result['changed_fields']));
        }

        if ($result['bank_change'] !== null) {
            $change = $result['bank_change'];

            event(new SellerBankDetailsChanged(
                profile: $result['profile'],
                isFirstTime: $change['is_first_time'],
                oldLast4: $change['old_last4'],
                newLast4: $change['new_last4'],
                oldBankName: $change['old_bank_name'],
                newBankName: $change['new_bank_name'],
            ));

            if ($change['verification_status'] === BankVerificationStatus::MISMATCH) {
                event(new SellerBankUnverified($result['profile'], $change['new_last4']));
            }
        }

        return $result['profile'];
    }
}
