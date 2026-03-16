<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'regex:/^212[5-7]\d{8}$/', 'unique:shops,phone'],
            'password'           => ['required', 'string', 'min:6', 'confirmed'],
            'verification_token' => ['required', 'string', 'size:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already registered.',
            'phone.regex' => 'Phone must be in format 212XXXXXXXXX.',
            'password.confirmed' => 'Password confirmation does not match.',
            'verification_token.required' => 'Phone verification is required.',
        ];
    }
}
