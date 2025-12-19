<?php

namespace App\Http\Requests;

use App\Enums\BloodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePatientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|regex:/^\+?[0-9]{10,15}$/',
            'allergies' => 'nullable|array',
            'allergies.*' => 'string|max:100',
            'chronic_diseases' => 'nullable|array',
            'chronic_diseases.*' => 'string|max:100',
            'current_medications' => 'nullable|array',
            'current_medications.*' => 'string|max:200',
            'medical_notes' => 'nullable|string|max:2000',
            'insurance_provider' => 'nullable|string|max:100',
            'insurance_number' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'blood_type.enum' => 'فصيلة الدم غير صحيحة',
            'emergency_contact_name.max' => 'اسم جهة الاتصال يجب ألا يتجاوز 100 حرف',
            'emergency_contact_phone.regex' => 'رقم هاتف جهة الاتصال غير صحيح',
            'medical_notes.max' => 'الملاحظات الطبية يجب ألا تتجاوز 2000 حرف',
            'insurance_provider.max' => 'اسم شركة التأمين يجب ألا يتجاوز 100 حرف',
            'insurance_number.max' => 'رقم التأمين يجب ألا يتجاوز 50 حرف',
        ];
    }
}
