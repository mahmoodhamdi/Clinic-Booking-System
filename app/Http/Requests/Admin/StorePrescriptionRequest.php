<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
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
            'medical_record_id.required' => __('validation_messages.prescription_medical_record_required'),
            'medical_record_id.exists' => __('validation_messages.prescription_medical_record_exists'),
            'items.required' => __('validation_messages.prescription_items_required'),
            'items.min' => __('validation_messages.prescription_items_required'),
            'items.*.medication_name.required' => __('validation_messages.prescription_medication_required'),
            'items.*.dosage.required' => __('validation_messages.prescription_dosage_required'),
            'items.*.frequency.required' => __('validation_messages.prescription_frequency_required'),
            'items.*.duration.required' => __('validation_messages.prescription_duration_required'),
        ];
    }
}
