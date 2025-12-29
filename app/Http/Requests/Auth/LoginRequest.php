<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'phone' => [
                'required',
                'string',
                'regex:/^01[0125][0-9]{8}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
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
            'phone.required' => __('رقم الهاتف مطلوب.'),
            'phone.regex' => __('يرجى إدخال رقم هاتف مصري صحيح.'),
            'password.required' => __('كلمة المرور مطلوبة.'),
            'password.min' => __('كلمة المرور يجب أن تكون 6 أحرف على الأقل.'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/\s+/', '', $this->phone ?? ''),
            ]);
        }
    }
}
