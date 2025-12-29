'use client';

import { useState, useEffect, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useTranslations } from 'next-intl';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import { ArrowRight, Loader2, RefreshCw } from 'lucide-react';
import Link from 'next/link';

import { AuthLayout } from '@/components/layouts/AuthLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { authApi } from '@/lib/api/auth';

export default function VerifyOtpPage() {
  const t = useTranslations('auth');
  const router = useRouter();
  const [phone, setPhone] = useState<string>('');
  const [countdown, setCountdown] = useState(0);
  const [otpValues, setOtpValues] = useState<string[]>(['', '', '', '', '', '']);
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

  useEffect(() => {
    // Get phone from session storage
    const storedPhone = sessionStorage.getItem('reset_phone');
    if (!storedPhone) {
      router.push('/forgot-password');
      return;
    }
    setPhone(storedPhone);
    // Focus first input
    inputRefs.current[0]?.focus();
  }, [router]);

  useEffect(() => {
    // Countdown timer for resend
    if (countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    }
    return undefined;
  }, [countdown]);

  const verifyOtp = useMutation({
    mutationFn: (data: { phone: string; otp: string }) => authApi.verifyOtp(data),
    onSuccess: (response) => {
      if (response.data.verified) {
        // Store OTP for reset password page
        const otp = otpValues.join('');
        sessionStorage.setItem('reset_otp', otp);
        toast.success(t('otpVerified'));
        router.push('/reset-password');
      } else {
        toast.error(t('invalidOtp'));
        clearOtpInputs();
      }
    },
    onError: () => {
      toast.error(t('invalidOtp'));
      clearOtpInputs();
    },
  });

  const resendOtp = useMutation({
    mutationFn: () => authApi.forgotPassword({ phone }),
    onSuccess: () => {
      toast.success(t('otpSent'));
      setCountdown(60);
      clearOtpInputs();
    },
    onError: () => {
      toast.error(t('error'));
    },
  });

  const clearOtpInputs = useCallback(() => {
    setOtpValues(['', '', '', '', '', '']);
    inputRefs.current[0]?.focus();
  }, []);

  const handleOtpChange = useCallback((index: number, value: string) => {
    // Only allow digits
    if (!/^\d*$/.test(value)) return;

    const newOtpValues = [...otpValues];
    newOtpValues[index] = value.slice(-1);
    setOtpValues(newOtpValues);

    // Auto-focus next input
    if (value && index < 5) {
      inputRefs.current[index + 1]?.focus();
    }

    // Auto-submit when complete
    const otp = newOtpValues.join('');
    if (otp.length === 6) {
      verifyOtp.mutate({ phone, otp });
    }
  }, [otpValues, phone, verifyOtp]);

  const handleKeyDown = useCallback((index: number, e: React.KeyboardEvent) => {
    if (e.key === 'Backspace' && !otpValues[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
  }, [otpValues]);

  const handlePaste = useCallback((e: React.ClipboardEvent) => {
    e.preventDefault();
    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    const newOtpValues = pastedData.split('').concat(Array(6 - pastedData.length).fill(''));
    setOtpValues(newOtpValues);

    if (pastedData.length === 6) {
      verifyOtp.mutate({ phone, otp: pastedData });
    } else {
      // Focus the next empty input
      const nextIndex = pastedData.length;
      if (nextIndex < 6) {
        inputRefs.current[nextIndex]?.focus();
      }
    }
  }, [phone, verifyOtp]);

  const handleSubmit = useCallback((e: React.FormEvent) => {
    e.preventDefault();
    const otp = otpValues.join('');
    if (otp.length === 6) {
      verifyOtp.mutate({ phone, otp });
    }
  }, [otpValues, phone, verifyOtp]);

  const maskedPhone = phone ? `${phone.slice(0, 3)}****${phone.slice(-3)}` : '';

  return (
    <AuthLayout
      title={t('verifyOtp')}
      subtitle={
        <>
          {t('enterOtp')}
          <br />
          <span className="font-semibold text-foreground" dir="ltr">
            {maskedPhone}
          </span>
        </>
      }
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* OTP Input Fields */}
        <div className="flex justify-center gap-2" dir="ltr" onPaste={handlePaste}>
          {otpValues.map((value, index) => (
            <Input
              key={index}
              ref={(el) => {
                inputRefs.current[index] = el;
              }}
              type="text"
              inputMode="numeric"
              maxLength={1}
              value={value}
              onChange={(e) => handleOtpChange(index, e.target.value)}
              onKeyDown={(e) => handleKeyDown(index, e)}
              className="w-12 h-12 text-center text-xl font-bold"
              disabled={verifyOtp.isPending}
              aria-label={`Digit ${index + 1}`}
            />
          ))}
        </div>

        {/* Submit Button */}
        <Button
          type="submit"
          className="w-full"
          disabled={verifyOtp.isPending || otpValues.join('').length !== 6}
        >
          {verifyOtp.isPending ? (
            <>
              <Loader2 className="me-2 h-4 w-4 animate-spin" />
              {t('verifyOtp')}...
            </>
          ) : (
            t('verifyOtp')
          )}
        </Button>

        {/* Resend OTP */}
        <div className="text-center">
          {countdown > 0 ? (
            <p className="text-sm text-muted-foreground">
              {t('resendIn')} {countdown} {t('seconds')}
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
                <Loader2 className="me-2 h-4 w-4 animate-spin" />
              ) : (
                <RefreshCw className="me-2 h-4 w-4" />
              )}
              {t('resendOtp')}
            </Button>
          )}
        </div>

        {/* Back Link */}
        <div className="text-center">
          <Link
            href="/forgot-password"
            className="text-sm text-muted-foreground hover:text-foreground inline-flex items-center"
          >
            <ArrowRight className="me-1 h-4 w-4" />
            {t('back')}
          </Link>
        </div>
      </form>
    </AuthLayout>
  );
}
