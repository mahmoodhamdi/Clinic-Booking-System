<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'discount' => ['nullable', 'numeric', 'min:0', 'lte:amount'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'mark_as_paid' => ['nullable', 'boolean'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => __('validation_messages.appointment_id_required'),
            'appointment_id.exists' => __('validation_messages.appointment_id_exists'),
            'amount.required' => __('validation_messages.amount_required'),
            'amount.numeric' => __('validation_messages.amount_numeric'),
            'amount.min' => __('validation_messages.amount_min'),
            'discount.lte' => __('validation_messages.discount_lte'),
            'method.required' => __('validation_messages.method_required'),
            'method.enum' => __('validation_messages.method_invalid'),
        ];
    }
}
