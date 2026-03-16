<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^212[5-7]\d{8}$/'],
            'otp'   => ['required', 'string', 'min:4', 'max:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be in format 212XXXXXXXXX.',
            'otp.required' => 'Please enter the OTP code.',
        ];
    }
}
