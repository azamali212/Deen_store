<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class UploadSellerLogoRequest extends FormRequest
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
            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
        ];
    }
}
