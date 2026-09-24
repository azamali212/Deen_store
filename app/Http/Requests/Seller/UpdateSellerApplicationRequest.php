<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use App\Domain\Seller\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSellerApplicationRequest extends FormRequest
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
        // PATCH: only validate what was actually sent.
        return [
            'store_name' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'business_name' => ['sometimes', 'required', 'string', 'min:2', 'max:150'],
            'business_type' => ['sometimes', 'required', Rule::enum(BusinessType::class)],
        ];
    }
}
