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
            'status.enum' => 'حالة الحجز غير صحيحة',
            'date.date' => 'صيغة التاريخ غير صحيحة',
            'from_date.date' => 'صيغة تاريخ البداية غير صحيحة',
            'to_date.date' => 'صيغة تاريخ النهاية غير صحيحة',
            'to_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
            'patient_id.exists' => 'المريض غير موجود',
            'per_page.min' => 'عدد النتائج يجب أن يكون على الأقل 1',
            'per_page.max' => 'عدد النتائج يجب ألا يتجاوز 100',
        ];
    }
}
