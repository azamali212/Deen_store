<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class CloseSellerStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // C34 — the seller types their own store name back. The actual
            // comparison is a domain rule (SellerProfileService::close),
            // because only the service knows what the name is.
            'confirm_store_name' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm_store_name.required' => 'Type your store name exactly to confirm you want to close it.',
        ];
    }

    public function confirmStoreName(): string
    {
        return (string) $this->string('confirm_store_name');
    }

    public function reason(): ?string
    {
        $reason = $this->input('reason');

        return is_string($reason) && $reason !== '' ? $reason : null;
    }
}
