<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب',
            'file.file' => 'يجب أن يكون ملفاً صالحاً',
            'file.max' => 'حجم الملف يجب أن لا يتجاوز 10 ميجابايت',
            'file.mimes' => 'نوع الملف غير مدعوم. الأنواع المدعومة: jpg, jpeg, png, gif, pdf, doc, docx',
        ];
    }
}
