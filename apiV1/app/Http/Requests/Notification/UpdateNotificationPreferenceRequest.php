<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Domain\Notifications\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'max:120'],
            'channel' => ['required', 'string', Rule::in(array_map(static fn (NotificationChannel $channel): string => $channel->value, NotificationChannel::cases()))],
            'enabled' => ['required', 'boolean'],
        ];
    }
}