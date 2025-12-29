# Phase 7: Authentication Flow Completion

## Overview
هذه المرحلة تركز على إكمال صفحات المصادقة الناقصة (OTP & Reset Password).

**الأولوية:** حرجة - ميزة ناقصة
**الحالة:** لم يبدأ
**التقدم:** 0%
**يعتمد على:** Phase 1

---

## Pre-requisites Checklist
- [ ] Phase 1 completed
- [ ] Backend running: `composer dev`
- [ ] Frontend running: `cd frontend && npm run dev`

---

## Milestone 7.1: Implement Verify OTP Page

### المشكلة
صفحة التحقق من الـ OTP موجودة كـ stub لكنها غير مكتملة.

### الملف المتأثر
`frontend/src/app/(auth)/verify-otp/page.tsx`

### المهام

#### Task 7.1.1: Create Complete OTP Verification Page
```tsx
"use client";

import { useState, useEffect, useRef } from "react";
import { useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "sonner";
import Link from "next/link";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { authApi } from "@/lib/api/auth";
import { ArrowRight, Loader2, RefreshCw } from "lucide-react";

const otpSchema = z.object({
  otp: z.string().length(6, "الرمز يجب أن يكون 6 أرقام").regex(/^\d+$/, "الرمز يجب أن يحتوي على أرقام فقط"),
});

type OtpFormData = z.infer<typeof otpSchema>;

export default function VerifyOtpPage() {
  const router = useRouter();
  const [phone, setPhone] = useState<string>("");
  const [countdown, setCountdown] = useState(0);
  const [otpValues, setOtpValues] = useState<string[]>(["", "", "", "", "", ""]);
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

  const form = useForm<OtpFormData>({
    resolver: zodResolver(otpSchema),
    defaultValues: { otp: "" },
  });

  useEffect(() => {
    // Get phone from session storage
    const storedPhone = sessionStorage.getItem("reset_phone");
    if (!storedPhone) {
      router.push("/forgot-password");
      return;
    }
    setPhone(storedPhone);
  }, [router]);

  useEffect(() => {
    // Countdown timer for resend
    if (countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [countdown]);

  const verifyOtp = useMutation({
    mutationFn: (data: { phone: string; otp: string }) => authApi.verifyOtp(data),
    onSuccess: (response) => {
      // Store reset token
      sessionStorage.setItem("reset_token", response.data.reset_token);
      toast.success("تم التحقق بنجاح");
      router.push("/reset-password");
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "رمز التحقق غير صحيح");
      // Clear OTP inputs
      setOtpValues(["", "", "", "", "", ""]);
      inputRefs.current[0]?.focus();
    },
  });

  const resendOtp = useMutation({
    mutationFn: () => authApi.forgotPassword({ phone }),
    onSuccess: () => {
      toast.success("تم إرسال رمز جديد");
      setCountdown(60);
      setOtpValues(["", "", "", "", "", ""]);
      inputRefs.current[0]?.focus();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "حدث خطأ أثناء إرسال الرمز");
    },
  });

  const handleOtpChange = (index: number, value: string) => {
    if (!/^\d*$/.test(value)) return;

    const newOtpValues = [...otpValues];
    newOtpValues[index] = value.slice(-1);
    setOtpValues(newOtpValues);

    // Auto-focus next input
    if (value && index < 5) {
      inputRefs.current[index + 1]?.focus();
    }

    // Update form value
    const otp = newOtpValues.join("");
    form.setValue("otp", otp);

    // Auto-submit when complete
    if (otp.length === 6) {
      verifyOtp.mutate({ phone, otp });
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent) => {
    if (e.key === "Backspace" && !otpValues[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
  };

  const handlePaste = (e: React.ClipboardEvent) => {
    e.preventDefault();
    const pastedData = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);
    const newOtpValues = pastedData.split("").concat(Array(6 - pastedData.length).fill(""));
    setOtpValues(newOtpValues);
    form.setValue("otp", pastedData);

    if (pastedData.length === 6) {
      verifyOtp.mutate({ phone, otp: pastedData });
    }
  };

  const onSubmit = (data: OtpFormData) => {
    verifyOtp.mutate({ phone, otp: data.otp });
  };

  const maskedPhone = phone ? `${phone.slice(0, 3)}****${phone.slice(-3)}` : "";

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl">التحقق من الرمز</CardTitle>
          <CardDescription>
            أدخل الرمز المكون من 6 أرقام المرسل إلى
            <br />
            <span className="font-semibold text-foreground" dir="ltr">{maskedPhone}</span>
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            {/* OTP Input Fields */}
            <div className="flex justify-center gap-2" dir="ltr" onPaste={handlePaste}>
              {otpValues.map((value, index) => (
                <Input
                  key={index}
                  ref={(el) => { inputRefs.current[index] = el; }}
                  type="text"
                  inputMode="numeric"
                  maxLength={1}
                  value={value}
                  onChange={(e) => handleOtpChange(index, e.target.value)}
                  onKeyDown={(e) => handleKeyDown(index, e)}
                  className="w-12 h-12 text-center text-xl font-bold"
                  disabled={verifyOtp.isPending}
                />
              ))}
            </div>

            {form.formState.errors.otp && (
              <p className="text-sm text-destructive text-center">
                {form.formState.errors.otp.message}
              </p>
            )}

            {/* Submit Button */}
            <Button
              type="submit"
              className="w-full"
              disabled={verifyOtp.isPending || otpValues.join("").length !== 6}
            >
              {verifyOtp.isPending ? (
                <>
                  <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                  جاري التحقق...
                </>
              ) : (
                "تحقق"
              )}
            </Button>

            {/* Resend OTP */}
            <div className="text-center">
              {countdown > 0 ? (
                <p className="text-sm text-muted-foreground">
                  يمكنك إعادة الإرسال خلال {countdown} ثانية
                </p>
              ) : (
                <Button
                  type="button"
                  variant="ghost"
                  onClick={() => resendOtp.mutate()}
                  disabled={resendOtp.isPending}
                  className="text-sm"
                >
                  {resendOtp.isPending ? (
                    <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                  ) : (
                    <RefreshCw className="ml-2 h-4 w-4" />
                  )}
                  إعادة إرسال الرمز
                </Button>
              )}
            </div>

            {/* Back Link */}
            <div className="text-center">
              <Link
                href="/forgot-password"
                className="text-sm text-muted-foreground hover:text-foreground inline-flex items-center"
              >
                <ArrowRight className="ml-1 h-4 w-4" />
                العودة
              </Link>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
```

### Verification
```bash
cd frontend && npm run dev
# Navigate to /forgot-password, enter phone, then verify OTP page works
```

---

## Milestone 7.2: Implement Reset Password Page

### المشكلة
صفحة إعادة تعيين كلمة المرور غير موجودة.

### الملف المتأثر
`frontend/src/app/(auth)/reset-password/page.tsx`

### المهام

#### Task 7.2.1: Create Reset Password Page
```tsx
"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { toast } from "sonner";
import Link from "next/link";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { authApi } from "@/lib/api/auth";
import { Eye, EyeOff, Loader2, CheckCircle } from "lucide-react";

const resetPasswordSchema = z.object({
  password: z.string()
    .min(8, "كلمة المرور يجب أن تكون 8 أحرف على الأقل")
    .regex(/[A-Z]/, "يجب أن تحتوي على حرف كبير")
    .regex(/[a-z]/, "يجب أن تحتوي على حرف صغير")
    .regex(/[0-9]/, "يجب أن تحتوي على رقم")
    .regex(/[^A-Za-z0-9]/, "يجب أن تحتوي على رمز خاص"),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: "كلمتا المرور غير متطابقتين",
  path: ["password_confirmation"],
});

type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;

export default function ResetPasswordPage() {
  const router = useRouter();
  const [phone, setPhone] = useState<string>("");
  const [resetToken, setResetToken] = useState<string>("");
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const form = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      password: "",
      password_confirmation: "",
    },
  });

  useEffect(() => {
    const storedPhone = sessionStorage.getItem("reset_phone");
    const storedToken = sessionStorage.getItem("reset_token");

    if (!storedPhone || !storedToken) {
      router.push("/forgot-password");
      return;
    }

    setPhone(storedPhone);
    setResetToken(storedToken);
  }, [router]);

  const resetPassword = useMutation({
    mutationFn: (data: ResetPasswordFormData) =>
      authApi.resetPassword({
        phone,
        token: resetToken,
        password: data.password,
        password_confirmation: data.password_confirmation,
      }),
    onSuccess: () => {
      // Clear session storage
      sessionStorage.removeItem("reset_phone");
      sessionStorage.removeItem("reset_token");

      setIsSuccess(true);
      toast.success("تم تغيير كلمة المرور بنجاح");

      // Redirect to login after 3 seconds
      setTimeout(() => {
        router.push("/login");
      }, 3000);
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || "حدث خطأ أثناء تغيير كلمة المرور");
    },
  });

  const onSubmit = (data: ResetPasswordFormData) => {
    resetPassword.mutate(data);
  };

  const password = form.watch("password");

  const passwordRequirements = [
    { met: password.length >= 8, label: "8 أحرف على الأقل" },
    { met: /[A-Z]/.test(password), label: "حرف كبير" },
    { met: /[a-z]/.test(password), label: "حرف صغير" },
    { met: /[0-9]/.test(password), label: "رقم" },
    { met: /[^A-Za-z0-9]/.test(password), label: "رمز خاص (!@#$%^&*)" },
  ];

  if (isSuccess) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <Card className="w-full max-w-md">
          <CardContent className="pt-6">
            <div className="text-center space-y-4">
              <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <CheckCircle className="h-8 w-8 text-green-600" />
              </div>
              <h2 className="text-xl font-semibold">تم تغيير كلمة المرور</h2>
              <p className="text-muted-foreground">
                يمكنك الآن تسجيل الدخول بكلمة المرور الجديدة
              </p>
              <p className="text-sm text-muted-foreground">
                جاري التحويل إلى صفحة تسجيل الدخول...
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl">تعيين كلمة مرور جديدة</CardTitle>
          <CardDescription>
            أدخل كلمة المرور الجديدة
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            {/* Password Field */}
            <div className="space-y-2">
              <Label htmlFor="password">كلمة المرور الجديدة</Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  placeholder="••••••••"
                  {...form.register("password")}
                  className="pl-10"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {form.formState.errors.password && (
                <p className="text-sm text-destructive">
                  {form.formState.errors.password.message}
                </p>
              )}
            </div>

            {/* Password Requirements */}
            <div className="space-y-2">
              <p className="text-sm text-muted-foreground">متطلبات كلمة المرور:</p>
              <ul className="space-y-1">
                {passwordRequirements.map((req, index) => (
                  <li
                    key={index}
                    className={`text-sm flex items-center gap-2 ${
                      req.met ? "text-green-600" : "text-muted-foreground"
                    }`}
                  >
                    <div className={`w-2 h-2 rounded-full ${req.met ? "bg-green-600" : "bg-gray-300"}`} />
                    {req.label}
                  </li>
                ))}
              </ul>
            </div>

            {/* Confirm Password Field */}
            <div className="space-y-2">
              <Label htmlFor="password_confirmation">تأكيد كلمة المرور</Label>
              <div className="relative">
                <Input
                  id="password_confirmation"
                  type={showConfirmPassword ? "text" : "password"}
                  placeholder="••••••••"
                  {...form.register("password_confirmation")}
                  className="pl-10"
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                >
                  {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
              {form.formState.errors.password_confirmation && (
                <p className="text-sm text-destructive">
                  {form.formState.errors.password_confirmation.message}
                </p>
              )}
            </div>

            {/* Submit Button */}
            <Button
              type="submit"
              className="w-full"
              disabled={resetPassword.isPending}
            >
              {resetPassword.isPending ? (
                <>
                  <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                  جاري التحديث...
                </>
              ) : (
                "تحديث كلمة المرور"
              )}
            </Button>

            {/* Back to Login */}
            <div className="text-center">
              <Link
                href="/login"
                className="text-sm text-muted-foreground hover:text-foreground"
              >
                العودة لتسجيل الدخول
              </Link>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
```

### Verification
```bash
cd frontend && npm run dev
# Test full password reset flow
```

---

## Milestone 7.3: SMS Gateway Integration (Optional)

### المشكلة
الـ SMS gateway غير منفذ - يتم تسجيل الـ OTP في الـ logs فقط.

### الملفات المتأثرة
1. `app/Services/SmsService.php` (جديد)
2. `app/Http/Controllers/Api/AuthController.php`
3. `config/services.php`

### المهام

#### Task 7.3.1: Create SMS Service
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $provider;
    protected ?string $apiKey;
    protected ?string $senderId;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'log');
        $this->apiKey = config('services.sms.api_key');
        $this->senderId = config('services.sms.sender_id');
    }

    public function send(string $phone, string $message): bool
    {
        return match ($this->provider) {
            'twilio' => $this->sendViaTwilio($phone, $message),
            'vonage' => $this->sendViaVonage($phone, $message),
            default => $this->logMessage($phone, $message),
        };
    }

    protected function sendViaTwilio(string $phone, string $message): bool
    {
        try {
            $response = Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.token')
            )->asForm()->post(
                "https://api.twilio.com/2010-04-01/Accounts/" . config('services.twilio.sid') . "/Messages.json",
                [
                    'From' => config('services.twilio.from'),
                    'To' => $this->formatPhone($phone),
                    'Body' => $message,
                ]
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Twilio SMS Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function sendViaVonage(string $phone, string $message): bool
    {
        try {
            $response = Http::post('https://rest.nexmo.com/sms/json', [
                'api_key' => config('services.vonage.key'),
                'api_secret' => config('services.vonage.secret'),
                'from' => config('services.vonage.from'),
                'to' => $this->formatPhone($phone),
                'text' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Vonage SMS Error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function logMessage(string $phone, string $message): bool
    {
        Log::info('SMS Message', [
            'to' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    protected function formatPhone(string $phone): string
    {
        // Add Egypt country code if not present
        if (str_starts_with($phone, '0')) {
            return '+2' . $phone;
        }

        if (!str_starts_with($phone, '+')) {
            return '+20' . $phone;
        }

        return $phone;
    }

    public function sendOtp(string $phone, string $otp): bool
    {
        $message = "رمز التحقق الخاص بك هو: {$otp}\nصالح لمدة 15 دقيقة.";

        return $this->send($phone, $message);
    }
}
```

#### Task 7.3.2: Update config/services.php
```php
return [
    // ... existing config

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'), // 'log', 'twilio', 'vonage'
        'api_key' => env('SMS_API_KEY'),
        'sender_id' => env('SMS_SENDER_ID'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'vonage' => [
        'key' => env('VONAGE_KEY'),
        'secret' => env('VONAGE_SECRET'),
        'from' => env('VONAGE_FROM'),
    ],
];
```

#### Task 7.3.3: Update AuthController
```php
use App\Services\SmsService;

class AuthController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        // ... existing code to generate token

        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'token' => Hash::make($token),
                'attempts' => 0,
                'locked_until' => null,
                'created_at' => now(),
            ]
        );

        // Send OTP via SMS
        $sent = $this->smsService->sendOtp($request->phone, $token);

        if (!$sent && config('services.sms.provider') !== 'log') {
            return response()->json([
                'success' => false,
                'message' => __('Failed to send verification code. Please try again.'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('Verification code sent to your phone.'),
        ]);
    }
}
```

### Verification
```bash
php artisan test --filter=Auth
# In production, test with actual SMS provider
```

---

## Milestone 7.4: Test Complete Auth Flow

### المهام

#### Task 7.4.1: Write E2E Test for Auth Flow
```typescript
// frontend/e2e/auth-flow.spec.ts
import { test, expect } from '@playwright/test';

test.describe('Password Reset Flow', () => {
  test('should complete full password reset flow', async ({ page }) => {
    // Navigate to forgot password
    await page.goto('/forgot-password');

    // Enter phone
    await page.fill('input[name="phone"]', '01012345678');
    await page.click('button[type="submit"]');

    // Wait for OTP page
    await expect(page).toHaveURL('/verify-otp');

    // In test environment, get OTP from backend logs or test endpoint
    // For now, simulate OTP entry
    const otpInputs = page.locator('input[maxlength="1"]');
    await otpInputs.nth(0).fill('1');
    await otpInputs.nth(1).fill('2');
    await otpInputs.nth(2).fill('3');
    await otpInputs.nth(3).fill('4');
    await otpInputs.nth(4).fill('5');
    await otpInputs.nth(5).fill('6');

    // Wait for reset password page
    await expect(page).toHaveURL('/reset-password');

    // Enter new password
    await page.fill('input[name="password"]', 'NewPassword123!');
    await page.fill('input[name="password_confirmation"]', 'NewPassword123!');
    await page.click('button[type="submit"]');

    // Should redirect to login
    await expect(page).toHaveURL('/login', { timeout: 5000 });
  });
});
```

#### Task 7.4.2: Manual Test Checklist
- [ ] Enter phone on forgot password page
- [ ] Receive/see OTP (in logs for dev)
- [ ] Enter OTP correctly - should proceed
- [ ] Enter wrong OTP 5 times - should lock
- [ ] Enter new password meeting all requirements
- [ ] Password confirmation matches
- [ ] Success message shown
- [ ] Redirect to login
- [ ] Login with new password works

### Verification
```bash
cd frontend && npm run test:e2e -- --grep "Password Reset"
```

---

## Post-Phase Checklist

### Tests
- [ ] Backend tests pass: `php artisan test --filter=Auth`
- [ ] Frontend tests pass: `cd frontend && npm test`
- [ ] E2E tests pass: `cd frontend && npm run test:e2e`

### Functionality
- [ ] OTP page receives and validates codes
- [ ] Reset password page works with all validations
- [ ] Full flow works end-to-end
- [ ] Error messages display correctly
- [ ] Lockout after 5 failed attempts works

### Documentation
- [ ] Update PROGRESS.md
- [ ] Commit changes

---

## Completion Command

```bash
php artisan test && cd frontend && npm test && npm run build && cd .. && git add -A && git commit -m "feat(auth): implement Phase 7 - Authentication Flow Completion

- Implement Verify OTP page with 6-digit input
- Implement Reset Password page with validation
- Add SMS service for OTP delivery
- Add password strength requirements UI
- Add E2E tests for auth flow

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```
