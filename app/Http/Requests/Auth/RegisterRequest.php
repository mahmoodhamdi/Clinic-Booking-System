<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'phone:EG', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.phone' => 'رقم الهاتف غير صالح. يجب أن يكون رقم هاتف مصري.',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل.',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل.',
            'password.confirmed' => 'كلمة المرور غير متطابقة.',
        ];
    }
}
