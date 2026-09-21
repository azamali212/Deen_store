<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Domain\User\Enums\AddressType;
use App\Domain\User\Rules\ValidPhoneNumber;
use App\Domain\User\Rules\ValidPostalCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAddressRequest extends FormRequest
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
            'type' => [
                'required',
                Rule::enum(AddressType::class),
            ],
            'is_default' => [
                'nullable',
                'boolean',
            ],
            'label' => [
                'nullable',
                'string',
                'max:255',
            ],
            'recipient_name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone' => [
                'required',
                'string',
                'max:20',
                new ValidPhoneNumber,
            ],
            'address_line_1' => [
                'required',
                'string',
                'max:255',
            ],
            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'required',
                'string',
                'max:255',
            ],
            'state' => [
                'nullable',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
                new ValidPostalCode,
            ],
            'country_code' => [
                'required',
                'string',
                'size:2',
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }
}
