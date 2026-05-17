<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'exists:prescription_items,id'],
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
            'items.min' => __('validation_messages.prescription_items_required'),
            'items.*.medication_name.required' => __('validation_messages.prescription_medication_required'),
            'items.*.dosage.required' => __('validation_messages.prescription_dosage_required'),
            'items.*.frequency.required' => __('validation_messages.prescription_frequency_required'),
            'items.*.duration.required' => __('validation_messages.prescription_duration_required'),
        ];
    }
}
