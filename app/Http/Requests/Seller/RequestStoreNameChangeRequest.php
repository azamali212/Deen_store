<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class RequestStoreNameChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Same shape as the original application field. Uniqueness is a
            // domain rule (C44 — checked here AND again on approval), not a
            // `unique:` rule that would only ever look once.
            'store_name' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    public function storeName(): string
    {
        return trim((string) $this->string('store_name'));
    }
}
