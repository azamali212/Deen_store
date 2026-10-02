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

            // getPermissionNames() returns DIRECTLY assigned permissions only.
            // This app attaches permissions to ROLES and never to a user, so
            // that method returned [] for every user since the day this was
            // written. getAllPermissions() merges the ones held via roles.
            //
            // Note for super_admin: AuthServiceProvider also has a
            // Gate::before bypass, so they pass every gate regardless. Their
            // list is complete here only because RolePermissionMap gives the
            // role every permission explicitly — if that ever changed, this
            // list would go empty while they could still do everything.
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),

            'email_verified_at' => $this->email_verified_at,

            'last_login_at' => $this->last_login_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}