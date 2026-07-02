<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ActiveSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request,
    ): array {

        return [

            'id' => $this->id,

            'token_id' => $this->token_id,

            'device_name' => $this->device_name,

            'browser' => $this->browser,

            'operating_system' => $this->operating_system,

            'ip_address' => $this->ip_address,

            'panel' => $this->panel,

            'last_activity_at' => $this->last_activity_at,

            'created_at' => $this->created_at,

            'terminated_at' => $this->terminated_at,

            'is_current' => optional(
                $request->user()?->currentAccessToken()
            )->id === $this->token_id,

        ];
    }
}