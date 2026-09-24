<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Models\SellerDocumentRenewal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the SELLER sees about their own renewal. Deliberately does NOT
 * include the AI extract (their own name and CNIC number read back at
 * them) or the private file path (D7).
 *
 * @mixin SellerDocumentRenewal
 */
class SellerDocumentRenewalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type->value,
            'document_label' => $this->document_type->label(),
            'original_name' => $this->original_name,
            'size_bytes' => $this->size_bytes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'ai_status' => $this->ai_status?->value,
            'expires_on' => $this->extracted_expiry_date?->toDateString(),
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
