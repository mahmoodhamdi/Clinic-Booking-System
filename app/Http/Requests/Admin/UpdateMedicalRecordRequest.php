<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'diagnosis' => ['sometimes', 'required', 'string', 'max:5000'],
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
            'diagnosis.required' => 'التشخيص مطلوب',
            'diagnosis.max' => 'التشخيص يجب أن لا يتجاوز 5000 حرف',
            'follow_up_date.after' => 'تاريخ المتابعة يجب أن يكون بعد اليوم',
        ];
    }
}
