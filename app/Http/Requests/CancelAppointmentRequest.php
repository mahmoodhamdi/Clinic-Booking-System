<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'يرجى إدخال سبب الإلغاء',
            'reason.max' => 'سبب الإلغاء يجب ألا يتجاوز 500 حرف',
        ];
    }
}
