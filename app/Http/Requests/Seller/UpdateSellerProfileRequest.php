<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSellerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * "PK36 SCBL 0000-0011 2345 6702" and "pk36scbl00000011..." both become
     * "PK36SCBL0000001123456702" BEFORE validation, so the same account
     * written two ways is never treated as a "change".
     */
    protected function prepareForValidation(): void
    {
        foreach (['bank_account_number', 'bank_branch_code'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([
                    $field => strtoupper(
                        (string) preg_replace('/[\s-]+/', '', $this->input($field)),
                    ),
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // C6 — verified by the admin, locked after approval.
            'store_name' => ['prohibited'],
            'business_name' => ['prohibited'],
            'business_type' => ['prohibited'],

            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'business_address' => ['sometimes', 'nullable', 'string', 'max:255'],

            // The three bank fields travel together — a half-updated payout
            // account (new number, old title) is never saved.
            'bank_account_title' => ['required_with:bank_account_number,bank_name', 'string', 'min:3', 'max:100'],
            'bank_name' => ['required_with:bank_account_number,bank_account_title', 'string', 'min:2', 'max:100'],
            // C56 — SHAPE only. The old rule was one Pakistani regex, and
            // a request cannot know the seller's country without a lookup
            // of its own. BankAccountValidator checks the FORMAT in the
            // domain, where the country is already in hand.
            'bank_account_number' => [
                'required_with:bank_account_title,bank_name',
                'string',
                'min:6',
                'max:34',
            ],

            // P11-1 — a UK sort code or a US routing number. Countries
            // that use an IBAN simply leave it out.
            'bank_branch_code' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.prohibited' => 'The store name was verified during approval and cannot be changed.',
            'business_name.prohibited' => 'The business name was verified during approval and cannot be changed.',
            'business_type.prohibited' => 'The business type was verified during approval and cannot be changed.',
            'bank_account_number.min' => 'That account number is too short to be real.',
            'bank_account_number.max' => 'That account number is longer than any IBAN.',
        ];
    }
}
