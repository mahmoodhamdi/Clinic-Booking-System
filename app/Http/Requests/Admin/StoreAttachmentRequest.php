<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Allowed MIME types for file uploads (validated against actual file content).
     */
    protected const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /**
     * Blocked file extensions that could be dangerous.
     */
    protected const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'exe', 'sh', 'bat', 'cmd', 'com', 'msi',
        'js', 'vbs', 'ps1', 'jar', 'py', 'rb', 'pl',
        'htaccess', 'htpasswd', 'ini', 'config',
        'svg', 'html', 'htm', 'shtml', 'xhtml',
    ];

    public function authorize(): bool
    {
        return $this->user()?->isStaff() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB in KB
                'mimetypes:' . implode(',', self::ALLOWED_MIME_TYPES),
                function ($attribute, $value, $fail) {
                    $this->validateFileExtension($value, $fail);
                    $this->validateFileContent($value, $fail);
                },
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Validate that the file extension is not in the blocked list.
     */
    protected function validateFileExtension($file, $fail): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            $fail('نوع الملف غير مسموح به لأسباب أمنية.');
        }

        // Check for double extensions (e.g., file.php.jpg)
        $filename = $file->getClientOriginalName();
        foreach (self::BLOCKED_EXTENSIONS as $blockedExt) {
            if (preg_match('/\.' . preg_quote($blockedExt, '/') . '\./i', $filename)) {
                $fail('اسم الملف يحتوي على امتداد غير مسموح به.');
            }
        }
    }

    /**
     * Validate that the file content doesn't contain malicious code.
     */
    protected function validateFileContent($file, $fail): void
    {
        $content = file_get_contents($file->getPathname());

        // Check for PHP code signatures
        $dangerousPatterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script\b[^>]*>/i',
            '/\beval\s*\(/i',
            '/\bexec\s*\(/i',
            '/\bsystem\s*\(/i',
            '/\bpassthru\s*\(/i',
            '/\bshell_exec\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $fail('الملف يحتوي على محتوى غير آمن.');
                return;
            }
        }
    }

    public function messages(): array
    {
        return [
            'file.required' => 'الملف مطلوب',
            'file.max' => 'حجم الملف يجب أن لا يتجاوز 10 ميجابايت',
        ];
    }
}
