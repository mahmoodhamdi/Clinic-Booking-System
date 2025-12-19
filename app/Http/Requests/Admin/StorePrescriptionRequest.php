<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'medical_record_id' => ['required', 'exists:medical_records,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['required', 'string', 'max:255'],
            'items.*.frequency' => ['required', 'string', 'max:255'],
            'items.*.duration' => ['required', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'medical_record_id.required' => 'السجل الطبي مطلوب',
            'medical_record_id.exists' => 'السجل الطبي غير موجود',
            'items.required' => 'يجب إضافة دواء واحد على الأقل',
            'items.min' => 'يجب إضافة دواء واحد على الأقل',
            'items.*.medication_name.required' => 'اسم الدواء مطلوب',
            'items.*.dosage.required' => 'الجرعة مطلوبة',
            'items.*.frequency.required' => 'عدد المرات مطلوب',
            'items.*.duration.required' => 'المدة مطلوبة',
        ];
    }
}
