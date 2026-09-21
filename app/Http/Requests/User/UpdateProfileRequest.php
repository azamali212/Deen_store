<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\User\Enums\Gender;
use App\Domain\User\Enums\ProfileVisibility;
use App\Domain\User\Rules\ValidUsername;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
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
            'username' => [
                'required',
                'string',
                new ValidUsername,
                // Ignored by user_id, not by the profile's own id, since a
                // brand-new profile (still being created) has no id yet.
                Rule::unique('user_profiles', 'username')->ignore($this->user()->id, 'user_id'),
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],
            'gender' => [
                'nullable',
                Rule::enum(Gender::class),
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'website_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'occupation' => [
                'nullable',
                'string',
                'max:255',
            ],
            'company_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'country_code' => [
                'nullable',
                'string',
                'size:2',
            ],
            'timezone' => [
                'nullable',
                'timezone',
            ],
            'locale' => [
                'nullable',
                'string',
                'max:10',
            ],
            'profile_visibility' => [
                'nullable',
                Rule::enum(ProfileVisibility::class),
            ],
        ];
    }
}
