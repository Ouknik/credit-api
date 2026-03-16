<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^212[5-7]\d{8}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be in format 212XXXXXXXXX (Moroccan number).',
        ];
    }
}
