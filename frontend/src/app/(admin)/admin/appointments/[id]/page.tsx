'use client';

import { useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import Link from 'next/link';
import { useTranslations, useLocale } from 'next-intl';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { format } from 'date-fns';
import { toast } from 'sonner';
import {
  Calendar,
  Clock,
  User,
  Phone,
  FileText,
  CheckCircle2,
  XCircle,
  AlertCircle,
  ArrowLeft,
  StickyNote,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';
import { adminApi } from '@/lib/api/admin';
import { getErrorMessage } from '@/lib/api/client';
import { Breadcrumbs } from '@/components/shared/Breadcrumbs';
import { ConfirmDialog } from '@/components/shared/ConfirmDialog';
import type { AppointmentStatus } from '@/types';
import { getDateLocale } from '@/lib/utils';

type ActionType = 'confirm' | 'complete' | 'cancel' | 'no_show' | null;

interface StatusConfig {
  label: string;
  className: string;
  icon: React.ReactNode;
}

export default function AdminAppointmentDetailPage() {
  const t = useTranslations();
  const locale = useLocale();
  const params = useParams();
  const router = useRouter();
  const queryClient = useQueryClient();
  const id = Number(params.id);

  const [pendingAction, setPendingAction] = useState<ActionType>(null);

  // Fetch appointment details
  const { data, isLoading, isError } = useQuery({
    queryKey: ['adminAppointment', id],
    queryFn: () => adminApi.getAppointment(id),
    enabled: !!id && !isNaN(id),
    retry: (failureCount, error) => {
      // Do not retry on 404
      const apiError = error as { status?: number };
      if (apiError?.status === 404) return false;
      return failureCount < 2;
    },
  });

  const appointment = data?.data;

  // Mutations
  const confirmMutation = useMutation({
    mutationFn: () => adminApi.confirmAppointment(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointment', id] });
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      setPendingAction(null);
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
      setPendingAction(null);
    },
  });

  const completeMutation = useMutation({
    mutationFn: () => adminApi.completeAppointment(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointment', id] });
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      setPendingAction(null);
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
      setPendingAction(null);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: () => adminApi.cancelAppointment(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointment', id] });
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      setPendingAction(null);
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
      setPendingAction(null);
    },
  });

  const noShowMutation = useMutation({
    mutationFn: () => adminApi.markNoShow(id),
    onSuccess: () => {
      toast.success(t('common.success'));
      queryClient.invalidateQueries({ queryKey: ['adminAppointment', id] });
      queryClient.invalidateQueries({ queryKey: ['adminAppointments'] });
      setPendingAction(null);
    },
    onError: (error) => {
      toast.error(getErrorMessage(error));
      setPendingAction(null);
    },
  });

  const isAnyPending =
    confirmMutation.isPending ||
    completeMutation.isPending ||
    cancelMutation.isPending ||
    noShowMutation.isPending;

  const handleActionConfirm = () => {
    switch (pendingAction) {
      case 'confirm':
        confirmMutation.mutate();
        break;
      case 'complete':
        completeMutation.mutate();
        break;
      case 'cancel':
        cancelMutation.mutate();
        break;
      case 'no_show':
        noShowMutation.mutate();
        break;
    }
  };

  const getStatusConfig = (status: AppointmentStatus): StatusConfig => {
    switch (status) {
      case 'confirmed':
        return {
          label: t('patient.appointments.status.confirmed'),
          className: 'bg-success/10 text-success dark:bg-success/20',
          icon: <CheckCircle2 className="h-4 w-4" />,
        };
      case 'pending':
        return {
          label: t('patient.appointments.status.pending'),
          className: 'bg-warning/10 text-warning dark:bg-warning/20',
          icon: <Clock className="h-4 w-4" />,
        };
      case 'completed':
        return {
          label: t('patient.appointments.status.completed'),
          className: 'bg-info/10 text-info dark:bg-info/20',
          icon: <CheckCircle2 className="h-4 w-4" />,
        };
      case 'cancelled':
        return {
          label: t('patient.appointments.status.cancelled'),
          className: 'bg-destructive/10 text-destructive dark:bg-destructive/20',
          icon: <XCircle className="h-4 w-4" />,
        };
      case 'no_show':
        return {
          label: t('patient.appointments.status.no_show'),
          className: 'bg-muted text-foreground/80 dark:bg-muted',
          icon: <AlertCircle className="h-4 w-4" />,
        };
    }
  };

  const getConfirmDialogProps = () => {
    switch (pendingAction) {
      case 'confirm':
        return {
          title: t('admin.appointments.confirm'),
          description: t('admin.appointments.changeStatusDescription'),
          variant: 'default' as const,
        };
      case 'complete':
        return {
          title: t('admin.appointments.markCompleted'),
          description: t('admin.appointments.changeStatusDescription'),
          variant: 'default' as const,
        };
      case 'cancel':
        return {
          title: t('common.cancel'),
          description: t('admin.appointments.changeStatusDescription'),
          variant: 'destructive' as const,
        };
      case 'no_show':
        return {
          title: t('admin.appointments.markNoShow'),
          description: t('admin.appointments.changeStatusDescription'),
          variant: 'default' as const,
        };
      default:
        return {
          title: '',
          description: '',
          variant: 'default' as const,
        };
    }
  };

  const breadcrumbs = [
    { label: t('admin.dashboard.title'), href: '/admin/dashboard' },
    { label: t('admin.appointments.title'), href: '/admin/appointments' },
    { label: `#${id}` },
  ];

  // Loading skeleton
  if (isLoading) {
    return (
      <div className="space-y-6">
        <Skeleton className="h-6 w-48" />
        <Skeleton className="h-10 w-64" />
        <div className="grid gap-6 lg:grid-cols-3">
          <div className="lg:col-span-2 space-y-4">
            <Skeleton className="h-48" />
            <Skeleton className="h-32" />
          </div>
          <Skeleton className="h-64" />
        </div>
      </div>
    );
  }

  // Not found / error
  if (isError || !appointment) {
    return (
      <div className="space-y-6">
        <Breadcrumbs items={breadcrumbs} />
        <div className="flex flex-col items-center justify-center py-24 text-center">
          <AlertCircle className="h-16 w-16 text-muted-foreground mb-4" />
          <h2 className="text-xl font-semibold mb-2">{t('common.notFound')}</h2>
          <p className="text-muted-foreground mb-6">
            {t('admin.appointments.notFound')}
          </p>
          <Button asChild variant="outline">
            <Link href="/admin/appointments">
              <ArrowLeft className="h-4 w-4 me-2" />
              {t('admin.appointments.title')}
            </Link>
          </Button>
        </div>
      </div>
    );
  }

  const statusConfig = getStatusConfig(appointment.status);
  const dialogProps = getConfirmDialogProps();
  const isActionable = appointment.status === 'pending' || appointment.status === 'confirmed';

  return (
    <div className="space-y-6">
      {/* Breadcrumbs */}
      <Breadcrumbs items={breadcrumbs} />

      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => router.push('/admin/appointments')}
            aria-label={t('common.back')}
          >
            <ArrowLeft className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">
              {t('admin.appointments.appointmentDetail')} #{id}
            </h1>
            <p className="text-sm text-muted-foreground mt-0.5">
              {format(new Date(appointment.date), 'PPPP', { locale: getDateLocale(locale) })}
            </p>
          </div>
        </div>
        <Badge className={statusConfig.className}>
          {statusConfig.icon}
          <span className="ms-1">{statusConfig.label}</span>
        </Badge>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main Info */}
        <div className="lg:col-span-2 space-y-6">
          {/* Appointment Details Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5 text-primary" />
                {t('admin.appointments.appointmentInfo')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <Calendar className="h-3.5 w-3.5" />
                    {t('common.date')}
                  </p>
                  <p className="font-medium">
                    {format(new Date(appointment.date), 'PPP', { locale: getDateLocale(locale) })}
                  </p>
                </div>
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <Clock className="h-3.5 w-3.5" />
                    {t('common.time')}
                  </p>
                  <p className="font-medium">
                    {appointment.slot_time || appointment.time}
                    {appointment.end_time && (
                      <span className="text-muted-foreground text-sm"> &mdash; {appointment.end_time}</span>
                    )}
                  </p>
                </div>
              </div>

              <Separator />

              {/* Reason */}
              {appointment.reason && (
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <FileText className="h-3.5 w-3.5" />
                    {t('admin.appointments.reason')}
                  </p>
                  <p className="text-foreground">{appointment.reason}</p>
                </div>
              )}

              {/* Patient Notes */}
              {appointment.notes && (
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <StickyNote className="h-3.5 w-3.5" />
                    {t('common.notes')}
                  </p>
                  <p className="text-foreground whitespace-pre-wrap">{appointment.notes}</p>
                </div>
              )}

              {/* Admin Notes */}
              {appointment.admin_notes && (
                <div className="space-y-1">
                  <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                    <StickyNote className="h-3.5 w-3.5" />
                    {t('admin.appointments.adminNotes')}
                  </p>
                  <p className="text-foreground whitespace-pre-wrap">{appointment.admin_notes}</p>
                </div>
              )}

              {/* Cancellation Reason */}
              {appointment.cancellation_reason && (
                <div className="space-y-1 rounded-lg bg-destructive/5 p-3">
                  <p className="text-sm text-destructive font-medium">
                    {t('admin.appointments.cancellationReason')}
                  </p>
                  <p className="text-foreground">{appointment.cancellation_reason}</p>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Timeline Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Clock className="h-5 w-5 text-primary" />
                {t('admin.appointments.timeline')}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <ol className="relative border-s border-border ms-3 space-y-4">
                <li className="ms-6">
                  <span className="absolute flex items-center justify-center w-6 h-6 bg-success/10 rounded-full -start-3 ring-8 ring-background">
                    <CheckCircle2 className="h-3.5 w-3.5 text-success" />
                  </span>
                  <h3 className="text-sm font-semibold">{t('admin.appointments.created')}</h3>
                  <p className="text-xs text-muted-foreground">
                    {format(new Date(appointment.created_at), 'PPp', { locale: getDateLocale(locale) })}
                  </p>
                </li>
                {appointment.confirmed_at && (
                  <li className="ms-6">
                    <span className="absolute flex items-center justify-center w-6 h-6 bg-info/10 rounded-full -start-3 ring-8 ring-background">
                      <CheckCircle2 className="h-3.5 w-3.5 text-info" />
                    </span>
                    <h3 className="text-sm font-semibold">{t('patient.appointments.status.confirmed')}</h3>
                    <p className="text-xs text-muted-foreground">
                      {format(new Date(appointment.confirmed_at), 'PPp', { locale: getDateLocale(locale) })}
                    </p>
                  </li>
                )}
                {appointment.completed_at && (
                  <li className="ms-6">
                    <span className="absolute flex items-center justify-center w-6 h-6 bg-primary/10 rounded-full -start-3 ring-8 ring-background">
                      <CheckCircle2 className="h-3.5 w-3.5 text-primary" />
                    </span>
                    <h3 className="text-sm font-semibold">{t('patient.appointments.status.completed')}</h3>
                    <p className="text-xs text-muted-foreground">
                      {format(new Date(appointment.completed_at), 'PPp', { locale: getDateLocale(locale) })}
                    </p>
                  </li>
                )}
                {appointment.cancelled_at && (
                  <li className="ms-6">
                    <span className="absolute flex items-center justify-center w-6 h-6 bg-destructive/10 rounded-full -start-3 ring-8 ring-background">
                      <XCircle className="h-3.5 w-3.5 text-destructive" />
                    </span>
                    <h3 className="text-sm font-semibold">{t('patient.appointments.status.cancelled')}</h3>
                    <p className="text-xs text-muted-foreground">
                      {format(new Date(appointment.cancelled_at), 'PPp', { locale: getDateLocale(locale) })}
                    </p>
                  </li>
                )}
              </ol>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Patient Info Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <User className="h-5 w-5 text-primary" />
                {t('admin.patients.title')}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {appointment.patient ? (
                <>
                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground">{t('common.name')}</p>
                    <p className="font-medium">{appointment.patient.name}</p>
                  </div>
                  <div className="space-y-1">
                    <p className="text-sm text-muted-foreground flex items-center gap-1.5">
                      <Phone className="h-3.5 w-3.5" />
                      {t('common.phone')}
                    </p>
                    <p className="font-medium">{appointment.patient.phone}</p>
                  </div>
                  <Separator />
                  <Button asChild variant="outline" className="w-full" size="sm">
                    <Link href={`/admin/patients`}>
                      <User className="h-4 w-4 me-2" />
                      {t('admin.patients.viewProfile')}
                    </Link>
                  </Button>
                </>
              ) : (
                <p className="text-muted-foreground text-sm">{t('common.noData')}</p>
              )}
            </CardContent>
          </Card>

          {/* Actions Card */}
          {isActionable && (
            <Card>
              <CardHeader>
                <CardTitle>{t('admin.appointments.actions')}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {appointment.status === 'pending' && (
                  <Button
                    className="w-full"
                    onClick={() => setPendingAction('confirm')}
                    disabled={isAnyPending}
                  >
                    <CheckCircle2 className="h-4 w-4 me-2 text-primary-foreground" />
                    {t('admin.appointments.confirm')}
                  </Button>
                )}
                {(appointment.status === 'pending' || appointment.status === 'confirmed') && (
                  <>
                    <Button
                      className="w-full"
                      variant="secondary"
                      onClick={() => setPendingAction('complete')}
                      disabled={isAnyPending}
                    >
                      <CheckCircle2 className="h-4 w-4 me-2" />
                      {t('admin.appointments.markCompleted')}
                    </Button>
                    <Button
                      className="w-full"
                      variant="outline"
                      onClick={() => setPendingAction('no_show')}
                      disabled={isAnyPending}
                    >
                      <AlertCircle className="h-4 w-4 me-2" />
                      {t('admin.appointments.markNoShow')}
                    </Button>
                    <Button
                      className="w-full"
                      variant="destructive"
                      onClick={() => setPendingAction('cancel')}
                      disabled={isAnyPending}
                    >
                      <XCircle className="h-4 w-4 me-2" />
                      {t('common.cancel')}
                    </Button>
                  </>
                )}
              </CardContent>
            </Card>
          )}
        </div>
      </div>

      {/* Confirm Dialog */}
      <ConfirmDialog
        open={pendingAction !== null}
        onOpenChange={(open) => {
          if (!open) setPendingAction(null);
        }}
        title={dialogProps.title}
        description={dialogProps.description}
        confirmLabel={t('common.confirm')}
        cancelLabel={t('common.cancel')}
        variant={dialogProps.variant}
        loading={isAnyPending}
        onConfirm={handleActionConfirm}
      />
    </div>
  );
}
