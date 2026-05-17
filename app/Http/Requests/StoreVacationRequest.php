<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVacationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('validation_messages.vacation_title_required'),
            'start_date.required' => __('validation_messages.vacation_start_required'),
            'start_date.after_or_equal' => __('validation_messages.vacation_start_after_today'),
            'end_date.required' => __('validation_messages.vacation_end_required'),
            'end_date.after_or_equal' => __('validation_messages.vacation_end_after_start'),
        ];
    }
}
