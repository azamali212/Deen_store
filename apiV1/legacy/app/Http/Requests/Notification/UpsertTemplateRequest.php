<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Domain\Notifications\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertTemplateRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:120'],
            'channel' => ['required', 'string', Rule::in(array_map(static fn (NotificationChannel $channel): string => $channel->value, NotificationChannel::cases()))],
            'locale' => ['sometimes', 'string', 'max:10'],
            'subject_template' => ['nullable', 'string', 'max:5000'],
            'body_template' => ['required', 'string', 'max:20000'],
            'active' => ['sometimes', 'boolean'],
            'version' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}