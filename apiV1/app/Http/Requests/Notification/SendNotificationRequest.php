<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Domain\Notifications\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendNotificationRequest extends FormRequest
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
            'recipient_id' => ['required', 'integer', 'min:1'],
            'recipient_type' => ['sometimes', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:120'],
            'payload' => ['sometimes', 'array'],
            'channels' => ['sometimes', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in(array_map(static fn (NotificationChannel $channel): string => $channel->value, NotificationChannel::cases()))],
            'locale' => ['sometimes', 'string', 'max:10'],
            'actor_id' => ['sometimes', 'integer', 'min:1'],
            'idempotency_key' => ['sometimes', 'string', 'max:100'],
        ];
    }
}