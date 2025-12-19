'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { toast } from 'sonner';
import { KeyRound, ArrowLeft } from 'lucide-react';
import Link from 'next/link';

import { AuthLayout } from '@/components/layouts/AuthLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { verifyOtpSchema, VerifyOtpFormData } from '@/lib/validations/auth';
import { authApi } from '@/lib/api/auth';

export default function VerifyOtpPage() {
  const t = useTranslations('auth');
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);
  const [phone, setPhone] = useState('');

  useEffect(() => {
    const storedPhone = sessionStorage.getItem('reset_phone');
    if (!storedPhone) {
      router.push('/forgot-password');
      return;
    }
    setPhone(storedPhone);
  }, [router]);

  const form = useForm<VerifyOtpFormData>({
    resolver: zodResolver(verifyOtpSchema),
    defaultValues: {
      phone: '',
      otp: '',
    },
  });

  useEffect(() => {
    if (phone) {
      form.setValue('phone', phone);
    }
  }, [phone, form]);

  const onSubmit = async (data: VerifyOtpFormData) => {
    setIsLoading(true);
    try {
      const response = await authApi.verifyOtp(data);
      if (response.data.verified) {
        toast.success(t('success'));
        // Store OTP for reset password page
        sessionStorage.setItem('reset_otp', data.otp);
        router.push('/reset-password');
      } else {
        toast.error(t('invalidOtp'));
      }
    } catch {
      toast.error(t('invalidOtp'));
    } finally {
      setIsLoading(false);
    }
  };

  const handleResendOtp = async () => {
    if (!phone) return;
    setIsLoading(true);
    try {
      await authApi.forgotPassword({ phone });
      toast.success(t('otpSent'));
    } catch {
      toast.error(t('error'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <AuthLayout title={t('verifyOtp')} subtitle={t('enterOtp')}>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <FormField
            control={form.control}
            name="otp"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('otp')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <KeyRound className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      placeholder="000000"
                      maxLength={6}
                      className="ps-10 text-center text-2xl tracking-widest"
                      {...field}
                    />
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? (
              <span className="flex items-center gap-2">
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                {t('verifyOtp')}...
              </span>
            ) : (
              t('verifyOtp')
            )}
          </Button>

          <div className="text-center">
            <button
              type="button"
              onClick={handleResendOtp}
              disabled={isLoading}
              className="text-sm text-primary hover:underline"
            >
              {t('resendOtp')}
            </button>
          </div>

          <Link
            href="/forgot-password"
            className="flex items-center justify-center gap-2 text-sm text-gray-600 hover:text-primary"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('back')}
          </Link>
        </form>
      </Form>
    </AuthLayout>
  );
}
