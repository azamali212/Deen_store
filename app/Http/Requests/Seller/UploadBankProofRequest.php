<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class UploadBankProofRequest extends FormRequest
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
        // Same limits as onboarding documents (D6).
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
