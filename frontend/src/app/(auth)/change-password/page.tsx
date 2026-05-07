'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useTranslations } from 'next-intl';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Lock, Eye, EyeOff, ShieldAlert } from 'lucide-react';

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
import { changePasswordSchema, ChangePasswordFormData } from '@/lib/validations/auth';
import { authApi } from '@/lib/api/auth';
import { getErrorMessage } from '@/lib/api/client';
import { useAuthStore } from '@/lib/stores/auth';

export default function ChangePasswordPage() {
  const t = useTranslations('auth');
  const router = useRouter();
  const { user, fetchUser } = useAuthStore();
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);

  const isForced = user?.must_change_password === true;

  // If a non-forced user lands here directly, send them to their dashboard.
  // Forced users stay until they complete the change.
  useEffect(() => {
    if (user && !isForced) {
      router.replace(user.role === 'admin' || user.role === 'secretary' ? '/admin/dashboard' : '/dashboard');
    }
  }, [user, isForced, router]);

  const form = useForm<ChangePasswordFormData>({
    resolver: zodResolver(changePasswordSchema),
    defaultValues: {
      current_password: '',
      password: '',
      password_confirmation: '',
    },
  });

  const mutation = useMutation({
    mutationFn: authApi.changePassword,
    onSuccess: async () => {
      toast.success(t('passwordChangedSuccess'));
      // Refresh user so the cleared flag propagates before we redirect.
      await fetchUser();
      const u = useAuthStore.getState().user;
      router.push(u?.role === 'admin' || u?.role === 'secretary' ? '/admin/dashboard' : '/dashboard');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
    },
  });

  return (
    <AuthLayout
      title={t('changePassword')}
      subtitle={
        isForced ? (
          <div className="flex items-start gap-2 mt-2 p-3 rounded-lg bg-warning/10 border border-warning/30 text-start">
            <ShieldAlert className="h-5 w-5 text-warning shrink-0 mt-0.5" />
            <p className="text-sm text-foreground">{t('forcedPasswordChangeNotice')}</p>
          </div>
        ) : undefined
      }
    >
      <Form {...form}>
        <form onSubmit={form.handleSubmit((data) => mutation.mutate(data))} className="space-y-5">
          <FormField
            control={form.control}
            name="current_password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>{t('currentPassword')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type={showCurrent ? 'text' : 'password'}
                      className="ps-10 pe-10"
                      autoComplete="current-password"
                      {...field}
                    />
                    <button
                      type="button"
                      className="absolute end-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                      onClick={() => setShowCurrent(!showCurrent)}
                      aria-label={showCurrent ? 'hide' : 'show'}
                    >
                      {showCurrent ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </button>
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
                <FormLabel>{t('newPassword')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type={showNew ? 'text' : 'password'}
                      className="ps-10 pe-10"
                      autoComplete="new-password"
                      {...field}
                    />
                    <button
                      type="button"
                      className="absolute end-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                      onClick={() => setShowNew(!showNew)}
                      aria-label={showNew ? 'hide' : 'show'}
                    >
                      {showNew ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
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
                <FormLabel>{t('confirmNewPassword')}</FormLabel>
                <FormControl>
                  <div className="relative">
                    <Lock className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                      type={showNew ? 'text' : 'password'}
                      className="ps-10"
                      autoComplete="new-password"
                      {...field}
                    />
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <Button type="submit" className="w-full" disabled={mutation.isPending}>
            {mutation.isPending ? (
              <span className="flex items-center gap-2">
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                {t('changePassword')}...
              </span>
            ) : (
              t('changePassword')
            )}
          </Button>
        </form>
      </Form>
    </AuthLayout>
  );
}
