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
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'slot_duration' => ['required', 'integer', 'min:10', 'max:120'],
            'max_patients_per_slot' => ['required', 'integer', 'min:1', 'max:10'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:90'],
            'cancellation_hours' => ['required', 'integer', 'min:0', 'max:72'],
        ];
    }

    public function messages(): array
    {
        return [
            'clinic_name.required' => 'اسم العيادة مطلوب.',
            'doctor_name.required' => 'اسم الطبيب مطلوب.',
            'slot_duration.required' => 'مدة الموعد مطلوبة.',
            'slot_duration.min' => 'مدة الموعد يجب أن تكون 10 دقائق على الأقل.',
            'slot_duration.max' => 'مدة الموعد يجب ألا تتجاوز 120 دقيقة.',
            'max_patients_per_slot.required' => 'عدد المرضى لكل موعد مطلوب.',
            'advance_booking_days.required' => 'عدد أيام الحجز المسبق مطلوب.',
            'cancellation_hours.required' => 'ساعات الإلغاء مطلوبة.',
        ];
    }
}
