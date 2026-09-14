<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'language' => $this->language,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'theme' => $this->theme,
            'email_notifications' => $this->email_notifications,
            'sms_notifications' => $this->sms_notifications,
            'push_notifications' => $this->push_notifications,
            'marketing_notifications' => $this->marketing_notifications,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
