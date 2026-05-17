<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'day_of_week' => [
                'sometimes',
                'required',
                'integer',
                'between:0,6',
                Rule::unique('schedules', 'day_of_week')->ignore($this->route('schedule')),
            ],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['boolean'],
            'break_start' => ['nullable', 'date_format:H:i', 'required_with:break_end'],
            'break_end' => ['nullable', 'date_format:H:i', 'required_with:break_start'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.unique' => __('validation_messages.schedule_day_unique'),
            'end_time.after' => __('validation_messages.schedule_end_after_start'),
            'break_start.required_with' => __('validation_messages.schedule_break_start_required_with'),
            'break_end.required_with' => __('validation_messages.schedule_break_end_required_with'),
        ];
    }
}
