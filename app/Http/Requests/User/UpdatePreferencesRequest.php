<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePreferencesRequest extends FormRequest
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
            'language' => [
                'nullable',
                'string',
                'max:10',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3',
            ],
            'timezone' => [
                'nullable',
                'timezone',
            ],
            'theme' => [
                'nullable',
                Rule::in(['light', 'dark', 'system']),
            ],
            'email_notifications' => [
                'nullable',
                'boolean',
            ],
            'sms_notifications' => [
                'nullable',
                'boolean',
            ],
            'push_notifications' => [
                'nullable',
                'boolean',
            ],
            'marketing_notifications' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
