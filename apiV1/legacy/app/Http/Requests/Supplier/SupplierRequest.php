<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:suppliers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'supplier_category_id' => ['required', 'exists:supplier_categories,id'],
            'is_preferred' => ['boolean'],
            'blacklisted' => ['boolean'],
            'blacklist_reason' => ['nullable', 'string', 'max:500'],
            'contract_status' => ['nullable', 'string', 'in:active,expired,pending'],
            'performance_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
