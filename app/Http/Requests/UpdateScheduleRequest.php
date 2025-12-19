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
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i', 'required_with:break_start'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.unique' => 'يوجد جدول لهذا اليوم بالفعل.',
            'end_time.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية.',
            'break_end.required_with' => 'وقت نهاية الاستراحة مطلوب عند تحديد وقت البداية.',
        ];
    }
}
