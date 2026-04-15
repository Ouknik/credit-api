<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmProcurementDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'Confirmation PIN is required.',
            'pin.size' => 'Confirmation PIN must contain 6 digits.',
            'pin.regex' => 'Confirmation PIN must contain only digits.',
        ];
    }
}