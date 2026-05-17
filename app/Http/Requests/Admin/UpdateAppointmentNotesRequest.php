<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
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
            'admin_notes.required' => __('validation_messages.admin_notes_required'),
            'admin_notes.max' => __('validation_messages.admin_notes_max'),
        ];
    }
}
