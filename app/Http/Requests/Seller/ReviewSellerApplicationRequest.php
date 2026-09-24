<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewSellerApplicationRequest extends FormRequest
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
            'decision' => ['required', 'in:approve,reject'],
            // The customer reads this in their email and fixes exactly
            // what it says — so a rejection without a reason is refused.
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required_if' => 'Please tell the applicant why the application is being rejected.',
        ];
    }
}
