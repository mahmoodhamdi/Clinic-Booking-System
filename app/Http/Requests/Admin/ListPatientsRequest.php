<?php

namespace App\Http\Requests\Admin;

use App\Enums\BloodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPatientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'search.max' => 'نص البحث يجب ألا يتجاوز 100 حرف',
            'status.in' => 'حالة المريض غير صحيحة',
            'blood_type.enum' => 'فصيلة الدم غير صحيحة',
            'per_page.min' => 'عدد النتائج يجب أن يكون على الأقل 1',
            'per_page.max' => 'عدد النتائج يجب ألا يتجاوز 100',
        ];
    }
}
