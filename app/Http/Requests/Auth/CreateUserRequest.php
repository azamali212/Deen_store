<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Domain\Permissions\Enums\SystemRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

final class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array

    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'role' => [
                'required',
                Rule::enum(SystemRole::class),
            ],
            // Seller only
            'store_name' => [
                Rule::requiredIf(
                    fn () => request('role') === SystemRole::SELLER->value
                ),
                'nullable',
                'string',
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
                'max:255',
            ],
        ];
    }
}
