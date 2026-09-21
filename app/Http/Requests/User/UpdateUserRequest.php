<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\User\Rules\ValidPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
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
        // {user} route param = the target user's ID being edited by an admin,
        // excluded from the uniqueness checks so saving without changing
        // email/phone doesn't false-positive against the user's own row.
        $userId = $this->route('user');

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
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                new ValidPhoneNumber,
                Rule::unique('users', 'phone')->ignore($userId),
            ],
        ];
    }
}
