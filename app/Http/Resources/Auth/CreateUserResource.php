<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CreateUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'success' => true,

            'message' => 'User account created successfully.',

            'data' => [

                'user' => [

                    'id' => $this->id,

                    'uuid' => $this->uuid,

                    'name' => $this->name,

                    'email' => $this->email,

                    'phone' => $this->phone,

                    'status' => $this->status,

                    'roles' => $this->getRoleNames()->values(),

                    'permissions' => $this->getPermissionNames()->values(),

                    'email_verified_at' => $this->email_verified_at,

                    'created_at' => $this->created_at,

                    'updated_at' => $this->updated_at,

                ],

            ],

            'meta' => [

                'request_id' => request()->header(
                    'X-Request-Id'
                ) ?? (string) str()->uuid(),

                'timestamp' => now()->toISOString(),

            ],

        ];
    }
}