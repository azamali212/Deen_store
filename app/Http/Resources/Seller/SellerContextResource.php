<?php

declare(strict_types=1);

namespace App\Http\Resources\Seller;

use App\Models\SellerTeamMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A1 — "which store am I in, and what may I do there".
 *
 * The user's Spatie roles and permissions already come back on the user
 * object, but they cannot answer this: an owner and a manager hold the
 * same `marketplace.stores` permission, and neither carries the store's
 * id, name or status.
 *
 * @mixin SellerTeamMember
 */
final class SellerContextResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->sellerProfile;

        return [
            'store_id' => $profile?->id,
            'store_name' => $profile?->store_name,

            'role' => $this->role->value,
            'role_label' => $this->role->label(),

            'store_status' => $profile?->status->value,
            'store_status_label' => $profile?->status->label(),

            // C33 — while this is false every write returns 403, whatever
            // the abilities below say. The frontend should show the reason
            // rather than a form that cannot be saved.
            'store_is_open' => $profile?->isOpen() ?? false,

            // Straight from SellerTeamRole::abilities() — one source of
            // truth shared by the server's guards and the client's UI.
            'can' => $this->role->abilities(),
        ];
    }
}
