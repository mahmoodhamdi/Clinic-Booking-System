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
            'from_date.date' => 'صيغة تاريخ البداية غير صحيحة',
            'to_date.date' => 'صيغة تاريخ النهاية غير صحيحة',
            'to_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
            'status.enum' => 'حالة الحجز غير صحيحة',
            'group_by.in' => 'قيمة التجميع غير صحيحة',
        ];
    }
}
