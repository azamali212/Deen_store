<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\User\Rules\ValidPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RequestPhoneVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                new ValidPhoneNumber,
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
        ];
    }
}
