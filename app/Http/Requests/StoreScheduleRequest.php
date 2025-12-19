<?php

namespace App\Http\Requests;

use App\Enums\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'day_of_week' => [
                'required',
                'integer',
                'between:0,6',
                Rule::unique('schedules', 'day_of_week'),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['boolean'],
            'break_start' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'break_end' => [
                'nullable',
                'date_format:H:i',
                'after:break_start',
                'before:end_time',
                'required_with:break_start',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.required' => 'يوم الأسبوع مطلوب.',
            'day_of_week.unique' => 'يوجد جدول لهذا اليوم بالفعل.',
            'start_time.required' => 'وقت البداية مطلوب.',
            'end_time.required' => 'وقت النهاية مطلوب.',
            'end_time.after' => 'وقت النهاية يجب أن يكون بعد وقت البداية.',
            'break_start.after' => 'وقت بداية الاستراحة يجب أن يكون بعد وقت البداية.',
            'break_end.after' => 'وقت نهاية الاستراحة يجب أن يكون بعد وقت بدايتها.',
            'break_end.before' => 'وقت نهاية الاستراحة يجب أن يكون قبل وقت النهاية.',
            'break_end.required_with' => 'وقت نهاية الاستراحة مطلوب عند تحديد وقت البداية.',
        ];
    }
}
