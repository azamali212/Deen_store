<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Domain\Seller\Enums\SellerDocumentType;
use App\Domain\Seller\Services\SellerDocumentRequirements;
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
            'country' => $this->country,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'rejection_reason' => $this->rejection_reason,
            'can_edit' => $this->status->isEditable(),
            'can_submit' => $this->status->isSubmittable(),
            'documents' => SellerApplicationDocumentResource::collection(
                $this->whenLoaded('documents'),
            ),
            // A3 — which boxes to SHOW, per country. A seller in Germany
            // must never be asked for a CNIC, and the frontend cannot work
            // that out for itself.
            'required_documents' => $this->requirementGroups(),

            // Which of those boxes are still empty. Follows whichever
            // identity option they started (P10-3), so somebody who
            // uploaded a passport is not told to go and find a driving
            // licence as well.
            'missing_documents' => $this->whenLoaded(
                'documents',
                fn (): array => app(SellerDocumentRequirements::class)->missingTypes(
                    (string) ($this->country ?? 'PK'),
                    $this->documents->map(fn ($document): string => $document->document_type->value)->all(),
                ),
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

    /**
     * The country's document groups, each with its options, labelled for
     * display. Read straight from CountryDocumentMap — the API never
     * repeats the rules, it reports them.
     *
     * @return array<string, array<int, array<int, array<string, string>>>>
     */
    private function requirementGroups(): array
    {
        $groups = app(SellerDocumentRequirements::class)
            ->groupsFor((string) ($this->country ?? 'PK'));

        return array_map(
            fn (array $options): array => array_map(
                fn (array $option): array => array_map(
                    fn (SellerDocumentType $type): array => [
                        'type' => $type->value,
                        'label' => $type->label(),
                    ],
                    $option,
                ),
                $options,
            ),
            $groups,
        );
    }
}
