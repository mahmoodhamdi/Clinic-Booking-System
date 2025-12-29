'use client';

import { useState, useEffect, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Lock, Eye, EyeOff, Loader2, CheckCircle, Circle } from 'lucide-react';
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
import { Card, CardContent } from '@/components/ui/card';
import { resetPasswordSchema, ResetPasswordFormData } from '@/lib/validations/auth';
import { authApi } from '@/lib/api/auth';

interface PasswordRequirement {
  met: boolean;
  label: string;
}

function PasswordRequirements({ password, t }: { password: string; t: (key: string) => string }) {
  const requirements = useMemo<PasswordRequirement[]>(() => [
    { met: password.length >= 6, label: t('passwordMinLength') },
    { met: /[A-Z]/.test(password), label: t('passwordUppercase') },
    { met: /[a-z]/.test(password), label: t('passwordLowercase') },
    { met: /[0-9]/.test(password), label: t('passwordNumber') },
    { met: /[^A-Za-z0-9]/.test(password), label: t('passwordSpecial') },
  ], [password, t]);

  const metCount = requirements.filter(r => r.met).length;
  const strengthPercentage = (metCount / requirements.length) * 100;

  const strengthColor = useMemo(() => {
    if (strengthPercentage < 40) return 'bg-red-500';
    if (strengthPercentage < 80) return 'bg-yellow-500';
    return 'bg-green-500';
  }, [strengthPercentage]);

  const strengthLabel = useMemo(() => {
    if (strengthPercentage < 40) return t('passwordWeak');
    if (strengthPercentage < 80) return t('passwordMedium');
    return t('passwordStrong');
  }, [strengthPercentage, t]);

  return (
    <div className="space-y-3">
      {/* Strength Bar */}
      <div className="space-y-1">
        <div className="flex justify-between text-xs">
          <span className="text-muted-foreground">{t('passwordStrength')}</span>
          <span className={`font-medium ${strengthPercentage >= 80 ? 'text-green-600' : strengthPercentage >= 40 ? 'text-yellow-600' : 'text-red-600'}`}>
            {password.length > 0 ? strengthLabel : ''}
          </span>
        </div>
        <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            className={`h-full transition-all duration-300 ${strengthColor}`}
            style={{ width: `${strengthPercentage}%` }}
          />
        </div>
      </div>

      {/* Requirements List */}
      <ul className="space-y-1.5">
        {requirements.map((req, index) => (
          <li
            key={index}
            className={`text-xs flex items-center gap-2 transition-colors ${
              req.met ? 'text-green-600' : 'text-muted-foreground'
            }`}
          >
            {req.met ? (
              <CheckCircle className="h-3.5 w-3.5" />
            ) : (
              <Circle className="h-3.5 w-3.5" />
            )}
            {req.label}
          </li>
        ))}
      </ul>
    </div>
  );
}

function SuccessState({ t }: { t: (key: string) => string }) {
  return (
    <Card className="w-full max-w-md">
      <CardContent className="pt-6">
        <div className="text-center space-y-4">
          <div className="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
            <CheckCircle className="h-8 w-8 text-green-600" />
          </div>
          <h2 className="text-xl font-semibold">{t('passwordChanged')}</h2>
          <p className="text-muted-foreground">
            {t('passwordChangedMessage')}
          </p>
          <p className="text-sm text-muted-foreground">
            {t('redirectingToLogin')}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

export default function ResetPasswordPage() {
  const t = useTranslations('auth');
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  const form = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      phone: '',
      otp: '',
      password: '',
      password_confirmation: '',
    },
  });

  useEffect(() => {
    const phone = sessionStorage.getItem('reset_phone');
    const otp = sessionStorage.getItem('reset_otp');

    if (!phone || !otp) {
      router.push('/forgot-password');
      return;
    }

    form.setValue('phone', phone);
    form.setValue('otp', otp);
  }, [router, form]);

  const resetPassword = useMutation({
    mutationFn: (data: ResetPasswordFormData) => authApi.resetPassword(data),
    onSuccess: () => {
      // Clear session storage
      sessionStorage.removeItem('reset_phone');
      sessionStorage.removeItem('reset_otp');

      setIsSuccess(true);
      toast.success(t('passwordChanged'));

      // Redirect to login after 3 seconds
      setTimeout(() => {
        router.push('/login');
      }, 3000);
    },
    onError: () => {
      toast.error(t('error'));
    },
  });

  const onSubmit = (data: ResetPasswordFormData) => {
    resetPassword.mutate(data);
  };

  const password = form.watch('password');

  if (isSuccess) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 p-4">
        <SuccessState t={t} />
      </div>
    );
  }

  return (
    <AuthLayout title={t('resetPassword')} subtitle={t('enterNewPassword')}>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <FormField
            control={form.control}
            name="password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('newPassword')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      type={showPassword ? 'text' : 'password'}
                      placeholder="••••••••"
                      className="ps-10 pe-10"
                      {...field}
                    />
                    <button
                      type="button"
                      className="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? (
                        <EyeOff className="h-4 w-4" />
                      ) : (
                        <Eye className="h-4 w-4" />
                      )}
                    </button>
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          {/* Password Requirements */}
          <PasswordRequirements password={password || ''} t={t} />

          <FormField
            control={form.control}
            name="password_confirmation"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('confirmPassword')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      type={showConfirmPassword ? 'text' : 'password'}
                      placeholder="••••••••"
                      className="ps-10 pe-10"
                      {...field}
                    />
                    <button
                      type="button"
                      className="absolute end-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                      onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    >
                      {showConfirmPassword ? (
                        <EyeOff className="h-4 w-4" />
                      ) : (
                        <Eye className="h-4 w-4" />
                      )}
                    </button>
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <Button type="submit" className="w-full" disabled={resetPassword.isPending}>
            {resetPassword.isPending ? (
              <>
                <Loader2 className="me-2 h-4 w-4 animate-spin" />
                {t('resetPassword')}...
              </>
            ) : (
              t('resetPassword')
            )}
          </Button>

          <div className="text-center">
            <Link
              href="/login"
              className="text-sm text-muted-foreground hover:text-foreground"
            >
              {t('backToLogin')}
            </Link>
          </div>
        </form>
      </Form>
    </AuthLayout>
  );
}
