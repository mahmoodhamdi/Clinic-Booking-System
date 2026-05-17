<?php

namespace App\Http\Requests;

use App\Enums\BloodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientProfileRequest extends FormRequest
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
            'blood_type.enum' => __('validation_messages.patient_blood_type_invalid'),
            'emergency_contact_name.max' => __('validation_messages.patient_emergency_name_max'),
            'emergency_contact_phone.regex' => __('validation_messages.patient_emergency_phone_regex'),
            'medical_notes.max' => __('validation_messages.patient_medical_notes_max'),
            'insurance_provider.max' => __('validation_messages.patient_insurance_provider_max'),
            'insurance_number.max' => __('validation_messages.patient_insurance_number_max'),
        ];
    }
}
