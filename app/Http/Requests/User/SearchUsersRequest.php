<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\Auth\Enums\UserAccountStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SearchUsersRequest extends FormRequest
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
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                Rule::enum(UserAccountStatus::class),
            ],
            'role' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email_verified' => [
                'nullable',
                'boolean',
            ],
            'phone_verified' => [
                'nullable',
                'boolean',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
