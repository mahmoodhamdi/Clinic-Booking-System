'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { toast } from 'sonner';
import { Eye, EyeOff, Phone, Lock, User, Mail } from 'lucide-react';

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
import { registerSchema, RegisterFormData } from '@/lib/validations/auth';
import { useAuthStore } from '@/lib/stores/auth';

export default function RegisterPage() {
  const t = useTranslations('auth');
  const router = useRouter();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const { register, isLoading } = useAuthStore();

  const form = useForm<RegisterFormData>({
    resolver: zodResolver(registerSchema),
    defaultValues: {
      name: '',
      phone: '',
      email: '',
      password: '',
      password_confirmation: '',
    },
  });

  const onSubmit = async (data: RegisterFormData) => {
    try {
      await register(data);
      toast.success(t('registerSuccess'));

      // Set cookies for middleware
      const token = useAuthStore.getState().token;
      const user = useAuthStore.getState().user;

      if (token) {
        document.cookie = `token=${token};path=/;max-age=86400`;
      }
      if (user) {
        document.cookie = `user=${JSON.stringify(user)};path=/;max-age=86400`;
      }

      router.push('/dashboard');
    } catch {
      // Error is handled by the store
    }
  };

  return (
    <AuthLayout title={t('register')}>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
          <FormField
            control={form.control}
            name="name"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('name')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <User className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      placeholder={t('name')}
                      className="ps-10"
                      {...field}
                    />
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

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

          <FormField
            control={form.control}
            name="email"
            render={({ field }) => (
              <FormItem>
                <FormLabel>
                  {t('email')} <span className="text-gray-400">(optional)</span>
                </FormLabel>
                <FormControl>
                  <div className="relative">
                    <Mail className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      type="email"
                      placeholder="email@example.com"
                      className="ps-10"
                      {...field}
                    />
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <FormField
            control={form.control}
            name="password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('password')}</FormLabel>
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

          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? (
              <span className="flex items-center gap-2">
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                {t('register')}...
              </span>
            ) : (
              t('register')
            )}
          </Button>

          <p className="text-center text-sm text-gray-600">
            {t('alreadyHaveAccount')}{' '}
            <Link href="/login" className="text-primary hover:underline">
              {t('login')}
            </Link>
          </p>
        </form>
      </Form>
    </AuthLayout>
  );
}
