<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewStoreNameChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['required_if:decision,reject', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function approves(): bool
    {
        return $this->string('decision')->toString() === 'approve';
    }

    public function reason(): ?string
    {
        $reason = $this->input('reason');

        return is_string($reason) && $reason !== '' ? $reason : null;
    }
}
