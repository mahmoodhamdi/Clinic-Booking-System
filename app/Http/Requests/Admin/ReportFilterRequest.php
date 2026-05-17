<?php

namespace App\Http\Requests\Admin;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'group_by' => 'nullable|in:day,week,month',
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.date' => __('validation_messages.report_from_date_invalid'),
            'to_date.date' => __('validation_messages.report_to_date_invalid'),
            'to_date.after_or_equal' => __('validation_messages.report_to_after_from'),
            'status.enum' => __('validation_messages.report_status_invalid'),
            'group_by.in' => __('validation_messages.report_group_by_invalid'),
        ];
    }
}
