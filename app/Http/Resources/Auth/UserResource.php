<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'uuid' => $this->uuid,

            'name' => $this->name,

            'email' => $this->email,

            'phone' => $this->phone,

            'status' => $this->status,

            'roles' => $this->getRoleNames()->values(),

            'permissions' => $this->getPermissionNames()->values(),

            'email_verified_at' => $this->email_verified_at,

            'last_login_at' => $this->last_login_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}