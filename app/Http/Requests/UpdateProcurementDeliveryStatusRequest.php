<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcurementDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:preparing,on_delivery'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Delivery status is required.',
            'status.in' => 'Status must be either preparing or on_delivery.',
        ];
    }
}