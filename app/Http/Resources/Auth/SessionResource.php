<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'token_id' => $this->token_id,
            'panel' => $this->panel?->value,
            'ip_address' => $this->ip_address,
            'device_name' => $this->device_name,
            'browser' => $this->browser,
            'operating_system' => $this->operating_system,
            'last_activity_at' => $this->last_activity_at,
            'terminated_at' => $this->terminated_at,
            'is_active' => $this->isActive(),
        ];
    }
}