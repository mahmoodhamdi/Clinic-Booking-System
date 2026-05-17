<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'patient_id' => 'nullable|exists:users,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|in:appointment_date,created_at,status',
            'order_dir' => 'nullable|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => __('validation_messages.report_status_invalid'),
            'date.date' => __('validation_messages.report_date_invalid'),
            'from_date.date' => __('validation_messages.report_from_date_invalid'),
            'to_date.date' => __('validation_messages.report_to_date_invalid'),
            'to_date.after_or_equal' => __('validation_messages.report_to_after_from'),
            'patient_id.exists' => __('validation_messages.report_patient_id_exists'),
            'per_page.min' => __('validation_messages.report_per_page_min'),
            'per_page.max' => __('validation_messages.report_per_page_max'),
        ];
    }
}
