<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

final class SendOtpRequest extends FormRequest
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
            'identifier' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string', 'max:80'],
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'ttl_minutes' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ];
    }
}