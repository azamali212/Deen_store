<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'order_number'      => $this->order_number,
            'customer_id'       => $this->customer_id,
            'product_id'        => $this->product_id,
            'vendor_id'         => $this->vendor_id,
            'subtotal'          => $this->subtotal,
            'discount'          => $this->discount,
            'tax_amount'        => $this->tax_amount,
            'shipping_amount'   => $this->shipping_amount,
            'grand_total'       => $this->grand_total,
            'payment_status'    => $this->payment_status,
            'order_status'      => $this->order_status,
            'tracking_number'   => $this->tracking_number,
            'shipping_address'  => $this->shipping_address,
            'billing_address'   => $this->billing_address,
            'order_date' => optional($this->order_date ? \Carbon\Carbon::parse($this->order_date) : null)->toDateTimeString(),
            'created_at'        => $this->created_at->toDateTimeString(),
            'updated_at'        => $this->updated_at->toDateTimeString(),
        ];
    }
}
