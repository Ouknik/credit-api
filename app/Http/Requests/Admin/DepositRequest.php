<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // AdminMiddleware already guards the route
    }

    public function rules(): array
    {
        return [
            'amount'      => ['required', 'numeric', 'min:100', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Le montant est obligatoire.',
            'amount.numeric'  => 'Le montant doit être un nombre.',
            'amount.min'      => 'Le montant minimum de dépôt est 100 MAD.',
            'amount.max'      => 'Le montant maximum de dépôt est 1 000 000 MAD.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
        ];
    }
}
