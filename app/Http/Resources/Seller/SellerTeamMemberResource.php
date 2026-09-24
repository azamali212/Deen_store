<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SellerTeamMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_owner' => $this->resource->isOwner(),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            // Only loaded on the invitation endpoints, where the invitee
            // needs to know WHICH store is asking.
            'store' => $this->whenLoaded('sellerProfile', fn (): array => [
                'id' => $this->sellerProfile->id,
                'store_name' => $this->sellerProfile->store_name,
            ]),
            'invited_by' => $this->whenLoaded('invitedBy', fn (): ?array => $this->invitedBy === null ? null : [
                'id' => $this->invitedBy->id,
                'name' => $this->invitedBy->name,
            ]),
            'invited_at' => $this->invited_at,
            'accepted_at' => $this->accepted_at,
            'revoked_at' => $this->revoked_at,
        ];
    }
}
