<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id', 'unique:medical_records,appointment_id'],
            'diagnosis' => ['required', 'string', 'max:5000'],
            'symptoms' => ['nullable', 'string', 'max:2000'],
            'examination_notes' => ['nullable', 'string', 'max:5000'],
            'treatment_plan' => ['nullable', 'string', 'max:5000'],
            'follow_up_date' => ['nullable', 'date', 'after:today'],
            'follow_up_notes' => ['nullable', 'string', 'max:1000'],
            'vital_signs' => ['nullable', 'array'],
            'vital_signs.blood_pressure' => ['nullable', 'string', 'max:20'],
            'vital_signs.heart_rate' => ['nullable', 'integer', 'min:30', 'max:250'],
            'vital_signs.temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'vital_signs.weight' => ['nullable', 'numeric', 'min:1', 'max:500'],
            'vital_signs.height' => ['nullable', 'numeric', 'min:30', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => __('validation_messages.appointment_id_required'),
            'appointment_id.exists' => __('validation_messages.appointment_id_exists'),
            'appointment_id.unique' => __('validation_messages.appointment_id_unique'),
            'diagnosis.required' => __('validation_messages.diagnosis_required'),
            'diagnosis.max' => __('validation_messages.diagnosis_max'),
            'follow_up_date.after' => __('validation_messages.follow_up_after_today'),
        ];
    }
}
