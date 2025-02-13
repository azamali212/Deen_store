<?php

namespace App\Http\Resources\Role;

use App\Http\Resources\Permission\PermissionResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'permissions' => PermissionResource::collection($this->permissions),
            'users' => UserResource::collection($this->users),
        ];
    }
}
