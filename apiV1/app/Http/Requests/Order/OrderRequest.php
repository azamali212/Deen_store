<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
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
        if ($this->routeIs('orders.changeStatus')) {
            return [
                'status' => 'required|string|in:pending,shipped,delivered,canceled',
            ];
        }
        return [
            'order_manager_id' => 'nullable|exists:order_managers,id',
            'store_manager_id' => 'nullable|exists:store_managers,id',
            'customer_id'      => 'required|exists:customers,id',
            'vendor_id'        => 'nullable|exists:vendors,id',
            'order_number'     => 'required|string|unique:orders,order_number',
            //'total_amount'     => 'required|numeric|min:0',
            'discount_amount'  => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
            'shipping_amount'  => 'nullable|numeric|min:0',
            'grand_total'      => 'required|numeric|min:0',
            'user_id'          => 'required|exists:users,id',
            'payment_status'   => 'required|string|in:pending,paid,failed',
            'order_status'     => 'required|string|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number'  => 'nullable|string|unique:orders,tracking_number',
            'shipping_address' => 'required|string',
            'billing_address'  => 'required|string',
        ];
    }
}
