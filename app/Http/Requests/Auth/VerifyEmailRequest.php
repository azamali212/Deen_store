<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [

            'token' => [
                'required',
                'string',
                'size:64',
            ],

        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [

            'token.required' => 'Verification token is required.',

            'token.string' => 'Verification token must be a string.',

            'token.size' => 'Invalid verification token.',

        ];
    }
}