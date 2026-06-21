<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],

            'store_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'business_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'business_type' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }
}