<?php

namespace App\Http\Requests\Admin;

use App\Enums\BloodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPatientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'has_profile' => 'nullable|boolean',
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'per_page' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|in:name,created_at,appointments_count',
            'order_dir' => 'nullable|in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'search.max' => __('validation_messages.list_search_max'),
            'status.in' => __('validation_messages.list_status_invalid'),
            'blood_type.enum' => __('validation_messages.patient_blood_type_invalid'),
            'per_page.min' => __('validation_messages.report_per_page_min'),
            'per_page.max' => __('validation_messages.report_per_page_max'),
        ];
    }
}
