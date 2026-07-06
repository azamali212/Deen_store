<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TrustedDeviceResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(
        Request $request,
    ): array {

        return [

            'id' => $this->id,

            'device_name' => $this->device_name,

            'fingerprint' => $this->fingerprint,

            'browser' => $this->browser,

            'operating_system' => $this->operating_system,

            'ip_address' => $this->ip_address,

            'trusted_until' => $this->trusted_until,

            'last_used_at' => $this->last_used_at,

            'created_at' => $this->created_at,

        ];
    }
}