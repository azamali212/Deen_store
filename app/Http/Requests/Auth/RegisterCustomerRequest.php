<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Public, unauthenticated self-registration — a brand-new visitor signing
 * themselves up as a customer. Deliberately has NO `role` field: unlike
 * CreateUserRequest (used by an already-authenticated admin who is
 * trusted to pick a role for someone else), a public endpoint must never
 * let the caller choose their own role from the request body — that
 * would let anyone register as super_admin. The role is hardcoded
 * server-side in BaseAuthController::registerSelf().
 */
final class RegisterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
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
                'unique:users,phone',
            ],
        ];
    }
}
