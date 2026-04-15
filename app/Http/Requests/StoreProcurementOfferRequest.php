<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'procurement_order_id' => ['required', 'uuid', 'exists:procurement_orders,id'],
            'delivery_cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_time' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.procurement_order_item_id' => ['required', 'uuid'],
            'items.*.is_available' => ['required', 'boolean'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'procurement_order_id.required' => 'Order is required.',
            'items.required' => 'Offer must include at least one item.',
            'items.min' => 'Offer must include at least one item.',
            'items.*.procurement_order_item_id.required' => 'Each offer item must reference an order item.',
            'items.*.is_available.required' => 'Each offer item must define availability.',
            'items.*.quantity.gt' => 'Offered quantity must be greater than zero.',
        ];
    }
}