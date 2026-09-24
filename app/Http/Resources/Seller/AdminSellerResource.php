<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin view of a LIVE store. Never exposes the bank account number or the
 * private path of the uploaded proof — only the masked account and status.
 */
final class AdminSellerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_name' => $this->store_name,
            'business_name' => $this->business_name,
            'business_type' => $this->business_type->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'owner' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            // P8-5 — separate from 'suspension' on purpose: the reviewer
            // must be able to see at a glance who ended this business.
            // C45 — 'store_name' above is still the LIVE name. This is only
            // what was asked for; nothing customer-facing has moved yet.
            'pending_name' => $this->when($this->pending_store_name !== null, fn (): array => [
                'requested' => $this->pending_store_name,
                'requested_at' => $this->store_name_requested_at?->toDateTimeString(),
            ]),

            'closure' => $this->when($this->closed_at !== null, fn (): array => [
                'closed_at' => $this->closed_at?->toDateTimeString(),
                'reason' => $this->closure_reason,
                'reopen_requested_at' => $this->reopen_requested_at?->toDateTimeString(),
                'awaiting_reopen_review' => $this->hasReopenRequestPending(),
            ]),

            'suspension' => $this->when($this->suspended_at !== null, fn (): array => [
                'reason' => $this->suspension_reason,
                'suspended_at' => $this->suspended_at,
                'suspended_by' => $this->whenLoaded('suspendedBy', fn (): ?array => $this->suspendedBy === null ? null : [
                    'id' => $this->suspendedBy->id,
                    'name' => $this->suspendedBy->name,
                ]),
            ]),
            'bank' => [
                'is_set' => $this->bank_account_last4 !== null,
                'account_title' => $this->bank_account_title,
                'bank_name' => $this->bank_name,
                'account_number_masked' => $this->resource->maskedBankAccount(),
                'verification_status' => $this->bank_verification_status?->value,
                'verification_label' => $this->bank_verification_status?->label(),
                'payout_ready' => $this->resource->isPayoutBankVerified(),
                'proof_uploaded_at' => $this->bank_proof_uploaded_at,
                'has_proof_pending' => $this->resource->hasBankProofPending(),
                'rejection_reason' => $this->bank_rejection_reason,
                'verified_at' => $this->bank_verified_at,
                'verified_by' => $this->whenLoaded('bankVerifiedBy', fn (): ?array => $this->bankVerifiedBy === null ? null : [
                    'id' => $this->bankVerifiedBy->id,
                    'name' => $this->bankVerifiedBy->name,
                ]),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
