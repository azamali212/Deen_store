<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
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
            'product_id' => ['required', 'exists:products,id'], // Ensure product exists
            'quantity' => ['required', 'integer', 'min:0'], // Quantity cannot be negative
            'auto_restock_threshold' => ['nullable', 'integer', 'min:0'], // Threshold must be positive
            'warehouse_id' => ['nullable', 'exists:warehouses,id'], // Must be a valid warehouse
        ];
    }

    /**
     * Custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'The product ID is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'quantity.required' => 'The quantity is required.',
            'quantity.integer' => 'The quantity must be a valid number.',
            'quantity.min' => 'The quantity cannot be negative.',
            'auto_restock_threshold.integer' => 'The auto-restock threshold must be a number.',
            'auto_restock_threshold.min' => 'The auto-restock threshold cannot be negative.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
        ];
    }
}
