<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'operator' => ['required', 'string', 'in:maroc_telecom,inwi,orange'],
            'amount' => ['required', 'numeric', 'min:5', 'max:1000'],
            'offer' => ['required', 'string', 'max:10'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'operator.in' => 'Invalid operator selected.',
            'amount.min' => 'Minimum recharge amount is 5 MAD.',
            'amount.max' => 'Maximum recharge amount is 1000 MAD.',
        ];
    }
}
