'use client';

import { useCallback } from 'react';
import { useTranslations, useLocale } from 'next-intl';
import { useQuery } from '@tanstack/react-query';
import Link from 'next/link';
import {
  CalendarPlus,
  Calendar,
  FileText,
  Clock,
  CheckCircle2,
  AlertCircle,
  User,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { useAuthStore } from '@/lib/stores/auth';
import { patientApi } from '@/lib/api/patient';
import { getIntlLocale } from '@/lib/utils';
import type { Appointment, ApiResponse, PatientDashboard } from '@/types';

function DashboardSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <Skeleton className="h-8 w-48" />
          <Skeleton className="h-4 w-32 mt-2" />
        </div>
        <Skeleton className="h-10 w-32" />
      </div>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[1, 2, 3, 4].map((i) => (
          <Skeleton key={i} className="h-24" />
        ))}
      </div>
      <Skeleton className="h-64" />
    </div>
  );
}

interface StatusBadgeProps {
  status: string;
  confirmedLabel: string;
  pendingLabel: string;
}

function StatusBadge({ status, confirmedLabel, pendingLabel }: StatusBadgeProps) {
  switch (status) {
    case 'confirmed':
      return (
        <Badge className="bg-success/10 text-success">
          <CheckCircle2 className="h-3 w-3 me-1" />
          {confirmedLabel}
        </Badge>
      );
    case 'pending':
      return (
        <Badge className="bg-warning/10 text-warning">
          <Clock className="h-3 w-3 me-1" />
          {pendingLabel}
        </Badge>
      );
    default:
      return null;
  }
}

interface AppointmentItemProps {
  appointment: Appointment;
  confirmedLabel: string;
  pendingLabel: string;
  intlLocale: string;
}

function AppointmentItem({ appointment, confirmedLabel, pendingLabel, intlLocale }: AppointmentItemProps) {
  return (
    <div className="flex items-center justify-between p-4 rounded-lg border bg-muted/50 hover:bg-muted transition-colors">
      <div className="flex items-center gap-4">
        <div className="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
          <Calendar className="h-6 w-6 text-primary" />
        </div>
        <div>
          <p className="font-medium">
            {new Date(appointment.date).toLocaleDateString(intlLocale, {
              weekday: 'long',
              month: 'long',
              day: 'numeric',
            })}
          </p>
          <p className="text-sm text-muted-foreground">{appointment.slot_time}</p>
        </div>
      </div>
      <div className="flex items-center gap-2">
        <StatusBadge
          status={appointment.status}
          confirmedLabel={confirmedLabel}
          pendingLabel={pendingLabel}
        />
      </div>
    </div>
  );
}

interface QuickActionCardProps {
  href: string;
  icon: React.ReactNode;
  iconBgColor: string;
  label: string;
}

function QuickActionCard({ href, icon, iconBgColor, label }: QuickActionCardProps) {
  return (
    <Link href={href}>
      <Card className="card-hover cursor-pointer">
        <CardContent className="p-4 flex flex-col items-center text-center">
          <div className={`h-12 w-12 rounded-xl ${iconBgColor} flex items-center justify-center mb-2`}>
            {icon}
          </div>
          <span className="text-sm font-medium">{label}</span>
        </CardContent>
      </Card>
    </Link>
  );
}

interface EmptyAppointmentsProps {
  message: string;
  buttonLabel: string;
}

function EmptyAppointments({ message, buttonLabel }: EmptyAppointmentsProps) {
  return (
    <div className="text-center py-8 animate-fade-in">
      <AlertCircle className="h-12 w-12 text-muted-foreground/70 mx-auto mb-4" />
      <p className="text-muted-foreground">{message}</p>
      <Button asChild className="mt-4">
        <Link href="/book">{buttonLabel}</Link>
      </Button>
    </div>
  );
}

export default function PatientDashboardPage() {
  const t = useTranslations();
  const locale = useLocale();
  const { user } = useAuthStore();

  // Fetch patient dashboard data
  const { data: dashboard, isLoading } = useQuery<ApiResponse<PatientDashboard>>({
    queryKey: ['patient-dashboard'],
    queryFn: () => patientApi.getDashboard(),
    refetchInterval: 60000, // Refresh every minute
  });

  const getConfirmedLabel = useCallback(() => {
    return t('patient.appointments.status.confirmed');
  }, [t]);

  const getPendingLabel = useCallback(() => {
    return t('patient.appointments.status.pending');
  }, [t]);

  if (isLoading) {
    return <DashboardSkeleton />;
  }

  const upcomingAppointments = dashboard?.data?.upcoming_appointments ?? [];
  const confirmedLabel = getConfirmedLabel();
  const pendingLabel = getPendingLabel();
  const intlLocale = getIntlLocale(locale);

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="bg-gradient-welcome rounded-2xl p-6 text-white relative overflow-hidden">
        <div className="absolute top-0 end-0 w-48 h-48 rounded-full bg-white/10 translate-x-16 -translate-y-16 pointer-events-none" />
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative">
          <div>
            <h1 className="text-2xl font-bold text-white">
              {t('patient.dashboard.welcome')}، {user?.name}
            </h1>
            <p className="text-white/80 mt-1">
              {new Date().toLocaleDateString(intlLocale, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}
            </p>
          </div>
          <Button asChild className="bg-white text-primary hover:bg-white/90 shadow-lg">
            <Link href="/book">
              <CalendarPlus className="h-4 w-4 me-2" />
              {t('patient.dashboard.bookNow')}
            </Link>
          </Button>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="animate-fade-in-up stagger-1">
          <QuickActionCard
            href="/book"
            icon={<CalendarPlus className="h-6 w-6 text-primary" />}
            iconBgColor="bg-primary/10"
            label={t('navigation.bookAppointment')}
          />
        </div>
        <div className="animate-fade-in-up stagger-2">
          <QuickActionCard
            href="/appointments"
            icon={<Calendar className="h-6 w-6 text-info" />}
            iconBgColor="bg-info/10"
            label={t('navigation.myAppointments')}
          />
        </div>
        <div className="animate-fade-in-up stagger-3">
          <QuickActionCard
            href="/medical-records"
            icon={<FileText className="h-6 w-6 text-success" />}
            iconBgColor="bg-success/10"
            label={t('navigation.medicalRecords')}
          />
        </div>
        <div className="animate-fade-in-up stagger-4">
          <QuickActionCard
            href="/profile"
            icon={<User className="h-6 w-6 text-chart-4" />}
            iconBgColor="bg-chart-4/10"
            label={t('navigation.profile')}
          />
        </div>
      </div>

      {/* Upcoming Appointments */}
      <Card className="animate-fade-in-up stagger-4">
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>{t('patient.dashboard.upcomingAppointments')}</CardTitle>
          <Button variant="ghost" size="sm" asChild>
            <Link href="/appointments">{t('patient.dashboard.viewAll')}</Link>
          </Button>
        </CardHeader>
        <CardContent>
          {upcomingAppointments.length > 0 ? (
            <div className="space-y-4">
              {upcomingAppointments.slice(0, 5).map((appointment) => (
                <AppointmentItem
                  key={appointment.id}
                  appointment={appointment}
                  confirmedLabel={confirmedLabel}
                  pendingLabel={pendingLabel}
                  intlLocale={intlLocale}
                />
              ))}
            </div>
          ) : (
            <EmptyAppointments
              message={t('patient.dashboard.noUpcoming')}
              buttonLabel={t('patient.dashboard.bookNow')}
            />
          )}
        </CardContent>
      </Card>
    </div>
  );
}
