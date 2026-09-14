<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Domain\User\Contracts\AvatarStorageInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender?->value,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_path !== null
                ? app(AvatarStorageInterface::class)->url($this->avatar_path)
                : null,
            'website_url' => $this->website_url,
            'occupation' => $this->occupation,
            'company_name' => $this->company_name,
            'country_code' => $this->country_code,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'profile_visibility' => $this->profile_visibility?->value,
            'profile_completion' => $this->profile_completion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
