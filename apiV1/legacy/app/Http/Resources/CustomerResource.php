<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'profile_picture' => $this->profile_picture ? url('storage/' . $this->profile_picture) : null,
            'post_code' => $this->post_code,
            'city' => $this->city,
            'country' => $this->country,
            'status' => $this->status,
            'preferred_language' => $this->preferred_language,
            'newsletter_subscription' => (bool) $this->newsletter_subscription,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
