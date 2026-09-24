<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Domain\Seller\Services\SellerProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerProfileResource extends JsonResource
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
            // P6-1 — a suspended store is read-only; the seller sees why.
            'suspension' => $this->when($this->suspended_at !== null, fn (): array => [
                'reason' => $this->suspension_reason,
                'suspended_at' => $this->suspended_at,
            ]),
            'logo_url' => app(SellerProfileService::class)->logoUrl($this->resource),
            'description' => $this->description,
            'business_address' => $this->business_address,
            // D3 — the full number NEVER leaves the server. Only the masked
            // form, built from the separate last4 column.
            'bank' => [
                'is_set' => $this->bank_account_last4 !== null,
                'account_title' => $this->bank_account_title,
                'bank_name' => $this->bank_name,
                'account_number_masked' => $this->resource->maskedBankAccount(),
                // P5-2 / P6-2
                'verification_status' => $this->bank_verification_status?->value,
                'verification_label' => $this->bank_verification_status?->label(),
                'payout_ready' => $this->resource->isPayoutBankVerified(),
                'proof_uploaded_at' => $this->bank_proof_uploaded_at,
                // Set when an admin rejected the statement — tells the
                // seller what to fix before uploading a new one.
                'rejection_reason' => $this->bank_rejection_reason,
                'can_upload_proof' => $this->bank_account_last4 !== null
                    && $this->bank_verification_status?->acceptsProof() === true,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
