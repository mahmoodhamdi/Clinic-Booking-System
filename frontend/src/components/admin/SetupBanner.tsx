'use client';

import Link from 'next/link';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useTranslations, useLocale } from 'next-intl';
import { toast } from 'sonner';
import { ShieldAlert, ArrowRight, ArrowLeft, Check } from 'lucide-react';

import { adminApi } from '@/lib/api/admin';
import { getErrorMessage, isApiError } from '@/lib/api/client';

// Renders only when clinic_settings.setup_completed_at is null. Surfaces
// two actions: "Open settings" (link to existing /admin/settings page) and
// "Mark setup complete" (calls POST /admin/settings/complete-setup; backend
// rejects with SETUP_INCOMPLETE if required fields are still placeholders).
export function SetupBanner() {
  const t = useTranslations('admin.setup');
  const locale = useLocale();
  const Forward = locale === 'ar' ? ArrowLeft : ArrowRight;
  const queryClient = useQueryClient();

  const { data } = useQuery({
    queryKey: ['clinicSettings'],
    queryFn: adminApi.getClinicSettings,
    refetchInterval: 60_000,
  });

  const completeMutation = useMutation({
    mutationFn: adminApi.completeClinicSetup,
    onSuccess: () => {
      toast.success(t('completeSuccess'));
      queryClient.invalidateQueries({ queryKey: ['clinicSettings'] });
    },
    onError: (error: unknown) => {
      // Backend returns 422 + error_code SETUP_INCOMPLETE when required
      // placeholder fields haven't been filled yet. Show the friendlier
      // copy instead of the raw API message in that case.
      if (isApiError(error) && error.status === 422) {
        toast.error(t('completeBlocked'));
      } else {
        toast.error(getErrorMessage(error));
      }
    },
  });

  if (!data?.data || data.data.is_setup_complete) {
    return null;
  }

  return (
    <div className="mx-4 sm:mx-6 mt-4 mb-2 rounded-xl border border-warning/40 bg-warning/10 p-4 flex flex-col sm:flex-row items-start gap-3">
      <ShieldAlert className="h-5 w-5 text-warning shrink-0 mt-0.5" />
      <div className="flex-1 min-w-0">
        <p className="font-semibold text-foreground">{t('title')}</p>
        <p className="text-sm text-muted-foreground mt-1">{t('description')}</p>
      </div>
      <div className="flex gap-2 shrink-0 w-full sm:w-auto">
        <Link
          href="/admin/settings"
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-card text-sm font-medium hover:bg-muted/50 transition-colors"
        >
          {t('cta')}
          <Forward className="h-3.5 w-3.5" />
        </Link>
        <button
          type="button"
          onClick={() => completeMutation.mutate()}
          disabled={completeMutation.isPending}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 transition-colors disabled:opacity-60"
        >
          <Check className="h-3.5 w-3.5" />
          {t('completeButton')}
        </button>
      </div>
    </div>
  );
}
