<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['nullable', 'in:manual,recharge'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
