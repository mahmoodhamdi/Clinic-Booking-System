<?php

namespace App\Http\Requests;

use App\Models\ClinicSetting;
use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->phone_verified_at !== null;
    }

    public function rules(): array
    {
        $settings = ClinicSetting::getInstance();
        $maxDate = now()->addDays($settings->advance_booking_days)->toDateString();

        return [
            'datetime' => [
                'required',
                'date',
                'after:now',
                'before_or_equal:'.$maxDate.' 23:59:59',
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'datetime.required' => __('validation_messages.appointment_datetime_required'),
            'datetime.date' => __('validation_messages.appointment_datetime_invalid'),
            'datetime.after' => __('validation_messages.appointment_date_past'),
            'datetime.before_or_equal' => __('validation_messages.appointment_date_too_far'),
            'notes.max' => __('validation_messages.appointment_notes_max'),
        ];
    }
}
