<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Domain\Seller\Enums\SellerDocumentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerApplicationResource extends JsonResource
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
            'rejection_reason' => $this->rejection_reason,
            'can_edit' => $this->status->isEditable(),
            'can_submit' => $this->status->isSubmittable(),
            'documents' => SellerApplicationDocumentResource::collection(
                $this->whenLoaded('documents'),
            ),
            // Lets the frontend show exactly which upload boxes are empty.
            'missing_documents' => $this->whenLoaded(
                'documents',
                fn (): array => array_values(array_diff(
                    SellerDocumentType::required(),
                    $this->documents->map(fn ($document): string => $document->document_type->value)->all(),
                )),
            ),
            'documents_count' => $this->whenCounted('documents'),
            // Admin side only (loaded by the admin actions, never by the
            // customer ones).
            'applicant' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'reviewed_by' => $this->whenLoaded('reviewer', fn (): array => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ]),
            // Layer 3 — admin only. The customer never sees risk scoring;
            // they only see hard failures, as the submit error.
            'ai_verification' => $this->when(
                $request->routeIs('admin.seller-applications.*'),
                fn (): array => [
                    'risk_level' => $this->ai_risk_level?->value,
                    'checked_at' => $this->ai_checked_at,
                    'report' => $this->ai_report,
                ],
            ),
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
