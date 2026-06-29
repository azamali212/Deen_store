<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => [
                'required',
                'email',
            ],
            'code' => [
                'required',
                'digits:6',
            ],
            'ip_address' => [
                'nullable',
                'string',
            ],
            'user_agent' => [
                'nullable',
                'string',
            ],
        ];
    }
}