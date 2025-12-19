<?php

namespace App\Http\Requests;

use App\Models\ClinicSetting;
use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                'before_or_equal:' . $maxDate . ' 23:59:59',
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'datetime.required' => 'يرجى تحديد موعد الحجز',
            'datetime.date' => 'صيغة التاريخ غير صحيحة',
            'datetime.after' => 'لا يمكن الحجز في الماضي',
            'datetime.before_or_equal' => 'لا يمكن الحجز بعد الحد الأقصى للحجز المسبق',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 500 حرف',
        ];
    }
}
