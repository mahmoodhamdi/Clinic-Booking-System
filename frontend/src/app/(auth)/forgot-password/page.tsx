'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { toast } from 'sonner';
import { Phone, ArrowLeft } from 'lucide-react';

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
import { forgotPasswordSchema, ForgotPasswordFormData } from '@/lib/validations/auth';
import { authApi } from '@/lib/api/auth';

export default function ForgotPasswordPage() {
  const t = useTranslations('auth');
  const router = useRouter();
  const [isLoading, setIsLoading] = useState(false);

  const form = useForm<ForgotPasswordFormData>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: {
      phone: '',
    },
  });

  const onSubmit = async (data: ForgotPasswordFormData) => {
    setIsLoading(true);
    try {
      await authApi.forgotPassword(data);
      toast.success(t('otpSent'));
      // Store phone in session storage for verify-otp page
      sessionStorage.setItem('reset_phone', data.phone);
      router.push('/verify-otp');
    } catch {
      toast.error(t('error'));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <AuthLayout title={t('forgotPassword')} subtitle={t('otpSent')}>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <FormField
            control={form.control}
            name="phone"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('phone')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <Phone className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      placeholder="01xxxxxxxxx"
                      className="ps-10"
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
                {t('submit')}...
              </span>
            ) : (
              t('submit')
            )}
          </Button>

          <Link
            href="/login"
            className="flex items-center justify-center gap-2 text-sm text-gray-600 hover:text-primary"
          >
            <ArrowLeft className="h-4 w-4" />
            {t('login')}
          </Link>
        </form>
      </Form>
    </AuthLayout>
  );
}
