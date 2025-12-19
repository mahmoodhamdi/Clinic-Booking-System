<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_notes' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'admin_notes.required' => 'يرجى إدخال الملاحظات',
            'admin_notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف',
        ];
    }
}
