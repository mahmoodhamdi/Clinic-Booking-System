<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'clinic_name' => ['required', 'string', 'max:255'],
            'doctor_name' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'about_text' => ['nullable', 'string', 'max:5000'],
            'services' => ['nullable', 'array', 'max:20'],
            'services.*.title' => ['required_with:services.*', 'string', 'max:120'],
            'services.*.description' => ['nullable', 'string', 'max:500'],
            'slot_duration' => ['required', 'integer', 'min:10', 'max:120'],
            'max_patients_per_slot' => ['required', 'integer', 'min:1', 'max:10'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:90'],
            'cancellation_hours' => ['required', 'integer', 'min:0', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'clinic_name.required' => __('validation_messages.settings_clinic_name_required'),
            'doctor_name.required' => __('validation_messages.settings_doctor_name_required'),
            'slot_duration.required' => __('validation_messages.settings_slot_duration_required'),
            'slot_duration.min' => __('validation_messages.settings_slot_duration_min'),
            'slot_duration.max' => __('validation_messages.settings_slot_duration_max'),
            'max_patients_per_slot.required' => __('validation_messages.settings_max_patients_required'),
            'advance_booking_days.required' => __('validation_messages.settings_advance_days_required'),
            'cancellation_hours.required' => __('validation_messages.settings_cancellation_hours_required'),
        ];
    }
}
